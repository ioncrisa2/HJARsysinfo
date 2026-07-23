<?php

namespace App\Http\Controllers\Api;

use App\Actions\Pembanding\SavePembandingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Http\Requests\App\PembandingUpdateRequest;
use App\Http\Requests\FindSimilarPembandingRequest;
use App\Http\Requests\PembandingIndexRequest;
use App\Http\Resources\PembandingResource;
use App\Http\Resources\SimilarPembandingResource;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Services\PembandingFactory;
use App\Services\PembandingService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

#[Group('Data Pembanding', 'Pencarian dan pengelolaan data pembanding properti.', weight: 4)]
class DataPembandingController extends Controller
{
    use ApiResponse;

    protected const MAX_INDEX_LIMIT = 200;

    protected const DEFAULT_INDEX_LIMIT = 50;

    protected const MAX_SIMILAR_LIMIT = 1000;

    protected const DEFAULT_SIMILAR_LIMIT = 100;

    protected const DEFAULT_RANGE_KM = 10.0;

    public function __construct(
        protected PembandingService $similarityService,
        protected PembandingFactory $factory,
        protected SavePembandingAction $savePembanding,
    ) {}

    #[Endpoint(
        title: 'Lihat daftar pembanding',
        description: 'Mengembalikan data pembanding terpaginasikan dan mendukung filter lokasi, jenis objek, peruntukan, serta rentang harga.'
    )]
    #[Response(
        status: 200,
        description: 'Daftar pembanding berhasil diambil.',
        type: "array{status: 'success', message: string, data: \Illuminate\Pagination\LengthAwarePaginator<\App\Http\Resources\PembandingResource>}"
    )]
    public function index(PembandingIndexRequest $request)
    {
        Gate::authorize('viewAny', Pembanding::class);

        $limit = $this->calculateLimit(
            $request->input('limit'),
            self::DEFAULT_INDEX_LIMIT,
            self::MAX_INDEX_LIMIT
        );

        $pembandings = Pembanding::query()
            ->with(['province', 'regency', 'district', 'village', 'creator'])
            ->filter($request->validated())
            ->orderByDesc('tanggal_data')
            ->paginate($limit);

        $payload = $pembandings->toArray();
        $payload['data'] = PembandingResource::collection($pembandings->getCollection())
            ->resolve($request);

        return $this->success(
            $payload,
            'Semua List Data Pembanding'
        );
    }

    #[Endpoint(
        title: 'Lihat detail pembanding',
        description: 'Mengembalikan satu data pembanding beserta relasi master data dan pembuatnya.'
    )]
    public function show(string $id)
    {
        $pembanding = Pembanding::with([
            'province',
            'regency',
            'district',
            'village',
            'creator',
            'jenisListing',
            'jenisObjek',
            'statusPemberiInformasi',
            'bentukTanah',
            'dokumenTanah',
            'posisiTanah',
            'kondisiTanah',
            'topografiRef',
            'peruntukanRef',
        ])->find($id);

        if (! $pembanding) {
            return $this->notFound("Data Pembanding dengan ID {$id} tidak ditemukan");
        }

        Gate::authorize('view', $pembanding);

        return $this->success(
            new PembandingResource($pembanding),
            'Data Ditemukan'
        );
    }

    #[Endpoint(
        title: 'Cari pembanding serupa berdasarkan ID',
        description: 'Menilai kemiripan terhadap satu data pembanding yang sudah tersimpan.'
    )]
    public function similarById(string $id, PembandingIndexRequest $request)
    {
        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        Gate::authorize('view', $pembanding);

        $limit = $this->calculateLimit(
            $request->input('limit'),
            self::DEFAULT_SIMILAR_LIMIT,
            self::MAX_SIMILAR_LIMIT
        );

        $rangeKm = $request->filled('range_km')
            ? (float) $request->input('range_km')
            : null;

        $radiusMeters = $this->calculateRadiusMeters($rangeKm);

        return $this->getSimilarResults($pembanding, $limit, $radiusMeters);
    }

    #[Endpoint(
        title: 'Lihat riwayat pembanding',
        description: 'Mengembalikan maksimal 100 perubahan terbaru beserta pelaku dan nilai field sebelum/sesudah perubahan.'
    )]
    #[Response(
        status: 200,
        description: 'Riwayat perubahan berhasil diambil.',
        type: "array{status: 'success', message: string, data: list<array{id: int, event: string, causer: string, causer_email: string|null, created_at: string|null, changes: list<array{field: string, old: mixed, new: mixed}>}>}"
    )]
    public function history(string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        Gate::authorize('view', $pembanding);

        $activities = $pembanding->activities()
            ->latest()
            ->with('causer:id,name,email')
            ->take(100)
            ->get()
            ->map(function ($activity) {
                $propertiesRaw = $activity->properties;

                if ($propertiesRaw instanceof Collection) {
                    $properties = $propertiesRaw->all();
                } elseif (is_array($propertiesRaw)) {
                    $properties = $propertiesRaw;
                } else {
                    $properties = [];
                }

                $attributes = data_get($properties, 'attributes', []);
                $old = data_get($properties, 'old', []);

                if (! is_array($attributes)) {
                    $attributes = [];
                }

                if (! is_array($old)) {
                    $old = [];
                }

                $changes = [];
                foreach ($attributes as $key => $newVal) {
                    $oldVal = $old[$key] ?? null;
                    if ($newVal === $oldVal) {
                        continue;
                    }
                    $changes[] = [
                        'field' => $key,
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }

                return [
                    'id' => $activity->id,
                    'event' => $activity->event ?? $activity->description,
                    'causer' => $activity->causer?->name ?? 'Sistem',
                    'causer_email' => $activity->causer?->email,
                    'created_at' => $activity->created_at?->toDateTimeString(),
                    'changes' => $changes,
                ];
            });

        return $this->success($activities, 'Riwayat perubahan data pembanding');
    }

    #[Endpoint(
        title: 'Ajukan penghapusan pembanding',
        description: 'Membuat permintaan penghapusan untuk dievaluasi moderator. Hanya satu permintaan pending diperbolehkan per data.'
    )]
    public function requestDelete(Request $request, string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        Gate::authorize('view', $pembanding);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penghapusan wajib diisi.',
        ]);

        $alreadyPending = $pembanding->deleteRequests()
            ->where('status', PembandingDeleteRequest::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'message' => 'Permintaan hapus sudah diajukan dan masih menunggu evaluasi moderator.',
            ], 422);
        }

        PembandingDeleteRequest::create([
            'pembanding_id' => $pembanding->id,
            'requested_by_id' => $request->user()->id,
            'reason' => trim($data['reason']),
            'status' => PembandingDeleteRequest::STATUS_PENDING,
        ]);

        return $this->success(null, 'Permintaan hapus berhasil dikirim dan menunggu evaluasi moderator.');
    }

    #[Endpoint(
        title: 'Tambah pembanding',
        description: 'Menyimpan data pembanding baru beserta foto properti melalui multipart/form-data.'
    )]
    public function store(PembandingStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Pembanding::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $pembanding = $this->savePembanding->create($data, $request->file('image'));

        $pembanding->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name',
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email',
        ]);

        return $this->success(
            new PembandingResource($pembanding),
            'Data pembanding berhasil ditambahkan.'
        );
    }

    #[Endpoint(
        title: 'Perbarui pembanding',
        description: 'Memperbarui data melalui PUT/PATCH, atau POST multipart dengan field _method=PUT sebagai workaround client.'
    )]
    public function update(PembandingUpdateRequest $request, string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        Gate::authorize('update', $pembanding);

        $data = $request->validated();

        $this->savePembanding->update($pembanding, $data, $request->file('image'));

        $pembanding->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name',
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email',
        ]);

        return $this->success(
            new PembandingResource($pembanding),
            'Data pembanding berhasil diperbarui.'
        );
    }

    #[Endpoint(
        title: 'Hapus pembanding',
        description: 'Melakukan soft delete langsung bagi pengguna yang memiliki permission penghapusan.'
    )]
    public function destroy(string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        Gate::authorize('delete', $pembanding);

        $pembanding->delete();

        return $this->success(null, 'Data pembanding berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Cari pembanding serupa berdasarkan kriteria',
        description: 'Mencari dan memberi peringkat data pembanding berdasarkan lokasi serta karakteristik properti yang dikirim.'
    )]
    public function similarByPayload(FindSimilarPembandingRequest $request)
    {
        Gate::authorize('viewAny', Pembanding::class);

        $validated = $request->validated();

        $input = $this->factory->createFromArray($validated);

        $limit = $validated['limit'] ?? self::DEFAULT_SIMILAR_LIMIT;
        $rangeKm = array_key_exists('range_km', $validated)
            ? (float) $validated['range_km']
            : null;
        $radiusMeters = $this->calculateRadiusMeters($rangeKm);

        return $this->getSimilarResults($input, $limit, $radiusMeters);
    }

    protected function getSimilarResults(Pembanding $input, int $limit, int $radiusMeters)
    {
        $scored = $this->similarityService->findSimilar($input, $limit, $radiusMeters);

        if ($scored->isEmpty()) {
            return $this->success([], 'Tidak ada data pembanding yang cocok');
        }

        $eloquent = EloquentCollection::make($scored->all());
        $eloquent->load(['province', 'regency', 'district', 'village', 'creator']);

        return $this->success(
            SimilarPembandingResource::collection($eloquent),
            'Beberapa data yang cocok'
        );
    }

    protected function calculateLimit(?int $requested, int $default, int $max): int
    {
        if ($requested === null) {
            return $default;
        }

        return min($requested, $max);
    }

    protected function calculateRadiusMeters(?float $rangeKm): int
    {
        $effectiveRangeKm = $rangeKm ?? self::DEFAULT_RANGE_KM;

        return (int) round($effectiveRangeKm * 1000);
    }
}
