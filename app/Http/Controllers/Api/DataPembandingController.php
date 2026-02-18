<?php

namespace App\Http\Controllers\Api;

use App\Models\Pembanding;
use App\Http\Controllers\Controller;
use App\Http\Resources\PembandingResource;
use App\Http\Resources\SimilarPembandingResource;
use App\Http\Requests\PembandingIndexRequest;
use App\Http\Requests\FindSimilarPembandingRequest;
use App\Services\PembandingService;
use App\Services\PembandingFactory;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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

        return $this->success($pembandings, 'Semua List Data Pembanding');
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
