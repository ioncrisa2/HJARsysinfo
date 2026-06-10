<?php

namespace App\Http\Controllers\Api;

use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\PembandingResource;
use App\Http\Resources\SimilarPembandingResource;
use App\Http\Requests\PembandingIndexRequest;
use App\Http\Requests\FindSimilarPembandingRequest;
use App\Services\PembandingService;
use App\Services\PembandingFactory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Http\Requests\App\PembandingUpdateRequest;

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
        protected PembandingFactory $factory
    ) {}

    /**
     * GET /api/v1/pembandings
     */
    public function index(PembandingIndexRequest $request)
    {
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

    /**
     * GET /api/v1/pembandings/{pembanding}
     */
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

        if (!$pembanding) {
            return $this->notFound("Data Pembanding dengan ID {$id} tidak ditemukan");
        }

        return $this->success(
            new PembandingResource($pembanding),
            'Data Ditemukan'
        );
    }

    /**
     * GET /api/v1/pembandings/{pembanding}/similar
     */
    public function similarById(string $id, PembandingIndexRequest $request)
    {
        $pembanding = Pembanding::find($id);

        if (!$pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

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

    /**
     * GET /api/v1/pembandings/{pembanding}/history
     */
    public function history(string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (!$pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

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

    /**
     * POST /api/v1/pembandings/{pembanding}/delete-request
     */
    public function requestDelete(Request $request, string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);

        if (!$pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

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
                'message' => 'Permintaan hapus sudah diajukan dan masih menunggu evaluasi super_admin.'
            ], 422);
        }

        PembandingDeleteRequest::create([
            'pembanding_id' => $pembanding->id,
            'requested_by_id' => $request->user()->id,
            'reason' => trim($data['reason']),
            'status' => PembandingDeleteRequest::STATUS_PENDING,
        ]);

        return $this->success(null, 'Permintaan hapus berhasil dikirim dan menunggu evaluasi super_admin.');
    }

    /**
     * POST /api/v1/pembandings
     */
    public function store(PembandingStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        $pembanding = Pembanding::create($data);

        $pembanding->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name', 
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email'
        ]);

        return $this->success(
            new PembandingResource($pembanding),
            'Data pembanding berhasil ditambahkan.'
        );
    }

    /**
     * PUT/PATCH /api/v1/pembandings/{pembanding}
     * or POST /api/v1/pembandings/{pembanding} with _method=PUT
     */
    public function update(PembandingUpdateRequest $request, string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);
        
        if (!$pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        $pembanding->update($data);

        $pembanding->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name', 
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email'
        ]);

        return $this->success(
            new PembandingResource($pembanding),
            'Data pembanding berhasil diperbarui.'
        );
    }

    /**
     * DELETE /api/v1/pembandings/{pembanding}
     */
    public function destroy(string $id): JsonResponse
    {
        $pembanding = Pembanding::find($id);
        
        if (!$pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        if (!auth()->user()->can('delete_data::pembanding')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk menghapus data secara langsung.'], 403);
        }

        $pembanding->delete();

        return $this->success(null, 'Data pembanding berhasil dihapus.');
    }

    private function storeImage(UploadedFile $file): string
    {
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('foto_pembanding', strtolower($filename), 'public');
    }

    /**
     * POST /api/v1/pembandings/similar
     */
    public function similarByPayload(FindSimilarPembandingRequest $request)
    {
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
