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

        $pembandings->getCollection()->transform(
            fn($item) => $this->transformImage($item)
        );

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
            'creator'
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

        return $this->getSimilarResults($pembanding, $limit);
    }

    /**
     * POST /api/v1/pembandings/similar
     */
    public function similarByPayload(FindSimilarPembandingRequest $request)
    {
        $validated = $request->validated();

        $input = $this->factory->createFromArray($validated);

        $limit = $validated['limit'] ?? self::DEFAULT_SIMILAR_LIMIT;

        return $this->getSimilarResults($input, $limit);
    }

    protected function getSimilarResults(Pembanding $input, int $limit)
    {
        $scored = $this->similarityService->findSimilar($input, $limit);

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

    protected function transformImage(Pembanding $item): Pembanding
    {
        if ($item->image) {
            $filename = ltrim($item->image, './');
            $item->image = asset('storage/foto_pembanding/' . $filename);
        }

        return $item;
    }
}
