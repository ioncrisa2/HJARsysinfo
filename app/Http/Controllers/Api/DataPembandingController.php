<?php

namespace App\Http\Controllers\Api;

use App\Actions\Pembanding\PreparePembandingDuplicateReviewAction;
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
use App\Models\User;
use App\Services\Pembanding\PembandingBrowseFilterService;
use App\Services\Pembanding\PembandingFormOptionsService;
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

    protected const DEFAULT_INDEX_LIMIT = 25;

    protected const MAX_SIMILAR_LIMIT = 1000;

    protected const DEFAULT_SIMILAR_LIMIT = 100;

    protected const DEFAULT_RANGE_KM = 10.0;

    public function __construct(
        protected PembandingService $similarityService,
        protected PembandingFactory $factory,
        protected SavePembandingAction $savePembanding,
        protected PembandingBrowseFilterService $browseFilterService,
        protected PembandingFormOptionsService $formOptionsService,
        protected PreparePembandingDuplicateReviewAction $prepareDuplicateReview,
    ) {}

    #[Endpoint(
        title: 'Lihat daftar pembanding',
        description: 'Mengembalikan data pembanding terpaginasikan dengan dukungan pencarian teks, filter wilayah hierarkis, jenis objek/listing, rentang tanggal dan harga.'
    )]
    public function index(PembandingIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Pembanding::class);

        $limit = $this->calculateLimit(
            $request->input('per_page') ?? $request->input('limit'),
            self::DEFAULT_INDEX_LIMIT,
            self::MAX_INDEX_LIMIT
        );

        $sort = $request->input('sort', 'tanggal_data');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Pembanding::query()
            ->with(['province:id,name', 'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email', 'jenisListing:id,name', 'jenisObjek:id,name'])
            ->filter($request->validated());

        $query = $this->browseFilterService->apply($query, $request->validated());

        if ($request->filled('min_harga')) {
            $query->where('harga', '>=', $request->input('min_harga'));
        }
        if ($request->filled('max_harga')) {
            $query->where('harga', '<=', $request->input('max_harga'));
        }
        if ($request->filled('min_luas_tanah')) {
            $query->where('luas_tanah', '>=', $request->input('min_luas_tanah'));
        }
        if ($request->filled('max_luas_tanah')) {
            $query->where('luas_tanah', '<=', $request->input('max_luas_tanah'));
        }

        $pembandings = $query
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar data pembanding berhasil diambil.',
            'data' => PembandingResource::collection($pembandings->getCollection()),
            'meta' => [
                'current_page' => $pembandings->currentPage(),
                'per_page' => $pembandings->perPage(),
                'from' => $pembandings->firstItem(),
                'to' => $pembandings->lastItem(),
                'total' => $pembandings->total(),
                'last_page' => $pembandings->lastPage(),
            ],
            'links' => [
                'first' => $pembandings->url(1),
                'last' => $pembandings->url($pembandings->lastPage()),
                'prev' => $pembandings->previousPageUrl(),
                'next' => $pembandings->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Opsi form pembuatan/edit pembanding',
        description: 'Mengembalikan opsi dropdown (dictionary aktif, provinsi, nilai default) untuk formulir data pembanding.'
    )]
    public function formOptions(Request $request): JsonResponse
    {
        $options = $this->formOptionsService->for($request->all());

        return $this->success($options, 'Opsi formulir pembanding berhasil diambil.');
    }

    #[Endpoint(
        title: 'Daftar kontributor/pembuat data pembanding',
        description: 'Mengembalikan daftar pengguna yang tercatat telah membuat minimal satu data pembanding.'
    )]
    public function creators(): JsonResponse
    {
        Gate::authorize('viewAny', Pembanding::class);

        $creators = User::query()
            ->whereHas('pembanding')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        return $this->success($creators, 'Daftar pembuat data pembanding.');
    }

    #[Endpoint(
        title: 'Lihat detail pembanding',
        description: 'Mengembalikan satu data pembanding beserta relasi master data dan pembuatnya.'
    )]
    public function show(string $id): JsonResponse
    {
        $pembanding = Pembanding::with([
            'province:id,name',
            'regency:id,name',
            'district:id,name',
            'village:id,name',
            'creator:id,name,email',
            'updater:id,name,email',
            'jenisListing:id,name,slug',
            'jenisObjek:id,name,slug',
            'statusPemberiInformasi:id,name,slug',
            'bentukTanah:id,name,slug',
            'dokumenTanah:id,name,slug',
            'posisiTanah:id,name,slug',
            'kondisiTanah:id,name,slug',
            'topografiRef:id,name,slug',
            'peruntukanRef:id,name,slug',
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
    public function similarById(string $id, PembandingIndexRequest $request): JsonResponse
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
            return $this->error('Permintaan hapus sudah diajukan dan masih menunggu evaluasi moderator.', 422, null, 'VALIDATION_FAILED');
        }

        $deleteRequest = PembandingDeleteRequest::create([
            'pembanding_id' => $pembanding->id,
            'requested_by_id' => $request->user()->id,
            'reason' => trim($data['reason']),
            'status' => PembandingDeleteRequest::STATUS_PENDING,
        ]);

        return $this->success($deleteRequest, 'Permintaan hapus berhasil dikirim dan menunggu evaluasi moderator.');
    }

    #[Endpoint(
        title: 'Tambah pembanding',
        description: 'Menyimpan data pembanding baru beserta foto properti melalui multipart/form-data. Jika terindikasi duplikat, mengembalikan HTTP 409 DUPLICATE_REVIEW_REQUIRED.'
    )]
    public function store(PembandingStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Pembanding::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $submission = $this->prepareDuplicateReview->execute(
                $request->user()->id,
                $data,
                $request->file('image'),
            );

            if ($submission) {
                $candidateIds = $submission->candidateIds();
                $firstCandidate = ! empty($candidateIds) ? Pembanding::withTrashed()->find($candidateIds[0]) : null;
                $isDeleted = $firstCandidate?->trashed() ?? false;
                $canView = $firstCandidate && ! $isDeleted && ($request->user()?->can('view', $firstCandidate) ?? false);

                return response()->json([
                    'status' => 'error',
                    'code' => 'DUPLICATE_REVIEW_REQUIRED',
                    'message' => $firstCandidate && $isDeleted
                        ? "Data identik sudah ada pada record #{$firstCandidate->id} yang telah dihapus. Pulihkan record tersebut, jangan membuat salinan baru."
                        : 'Data terindikasi duplikat dengan data pembanding yang sudah ada.',
                    'errors' => null,
                    'duplicate' => [
                        'id' => $firstCandidate?->id ?? ($candidateIds[0] ?? null),
                        'status' => $isDeleted ? 'deleted' : 'active',
                        'url' => $canView ? url("/api/v1/pembandings/{$firstCandidate->id}") : null,
                        'submission_id' => $submission->id,
                        'submission_url' => url("/api/v1/pembanding-submissions/{$submission->id}"),
                        'expires_at' => $submission->expires_at?->toISOString(),
                        'candidate_ids' => $candidateIds,
                    ],
                ], 409);
            }
        }

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
        $data['updated_by'] = $request->user()->id;

        $this->savePembanding->update($pembanding, $data, $request->file('image'));

        $pembanding->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name',
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email', 'updater:id,name,email',
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
    public function similarByPayload(FindSimilarPembandingRequest $request): JsonResponse
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

    protected function getSimilarResults(Pembanding $input, int $limit, int $radiusMeters): JsonResponse
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
