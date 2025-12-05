<?php

namespace App\Http\Controllers\Api;

use App\Models\Pembanding;
use App\Enums\Peruntukan;
use App\Enums\DokumenTanah;
use App\Enums\PosisiTanah;
use App\Enums\KondisiTanah;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PembandingResource;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Services\PembandingService;

class DataPembandingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PembandingService $similarityService
    )
    {}

    /**
     * GET /api/v1/pembandings
     */
    public function index(Request $request)
    {
        $query = Pembanding::query()
            ->with(['province', 'regency', 'district', 'village', 'creator']);

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('peruntukan')) {
            $query->where('peruntukan', $request->peruntukan);
        }

        if ($request->filled('jenis_objek')) {
            $query->where('jenis_objek', $request->jenis_objek);
        }

        if ($request->filled('min_harga')) {
            $query->where('harga', '>=', $request->min_harga);
        }

        if ($request->filled('max_harga')) {
            $query->where('harga', '<=', $request->max_harga);
        }

        $limit = (int) $request->get('limit', 50);
        $limit = $limit > 200 ? 200 : $limit;

        $pembandings = $query
            ->orderByDesc('tanggal_data')
            ->paginate($limit);

        $pembandings->getCollection()->transform(function ($item){
            if ($item->image) {
                $filename = ltrim($item->image, './');
                $item->image = asset('storage/foto_pembanding/' . $filename);
            }
            return $item;
        });

        return $this->success($pembandings, 'Semua List Data Pembanding', 200);
    }

    /**
     * GET /api/v1/pembandings/{pembanding}
     */
    public function show($id)
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

        return $this->success($pembanding, 'Data Ditemukan', 200);
    }

    /**
     * GET /api/v1/pembandings/{pembanding}/similar
     */
    public function similarById(Request $request, $id)
    {
        $limit = (int) $request->get('limit', 100);
        $limit = $limit > 1000 ? 1000 : $limit;

        $pembanding = Pembanding::find($id);

        if (! $pembanding) {
            return $this->notFound("Data pembanding dengan ID {$id} tidak ditemukan.");
        }

        $scored = $this->similarityService->findSimilar($pembanding, $limit);

        $eloquent = EloquentCollection::make($scored->all());
        $eloquent->load(['province', 'regency', 'district', 'village', 'creator']);

        $result = $eloquent->map(function ($item) {
            $res = (new PembandingResource($item))->toArray(request());
            $res['score'] = $item->score;
            $res['sql_distance'] = $item->sql_distance ?? null;
            return $res;
        });

        return $this->success($result, 'Beberapa data yang cocok', 200);
    }

    /**
     * POST /api/v1/pembandings/similar
     */
    public function similarByPayload(Request $request)
    {
        $data = $request->validate([
            'latitude'      => ['required', 'numeric'],
            'longitude'     => ['required', 'numeric'],
            'district_id'   => ['required', 'string'],
            'peruntukan'    => ['required', 'string'],
            'luas_tanah'    => ['nullable', 'numeric'],
            'luas_bangunan' => ['nullable', 'numeric'],
            'dokumen_tanah' => ['nullable', 'string'],
            'lebar_jalan'   => ['nullable', 'numeric'],
            'posisi_tanah'  => ['nullable', 'string'],
            'kondisi_tanah' => ['nullable', 'string'],
            'harga'         => ['nullable', 'numeric'],
            'limit'         => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $limit = $data['limit'] ?? 100;

        $input = new Pembanding();
        $input->latitude      = $data['latitude'];
        $input->longitude     = $data['longitude'];
        $input->district_id   = $data['district_id'];
        $input->luas_tanah    = $data['luas_tanah'] ?? 0;
        $input->luas_bangunan = $data['luas_bangunan'] ?? 0;
        $input->lebar_jalan   = $data['lebar_jalan'] ?? 0;
        $input->harga         = $data['harga'] ?? null;

        $input->peruntukan    = isset($data['peruntukan'])
            ? Peruntukan::from($data['peruntukan'])
            : null;

        $input->dokumen_tanah = isset($data['dokumen_tanah'])
            ? DokumenTanah::from($data['dokumen_tanah'])
            : null;

        $input->posisi_tanah  = isset($data['posisi_tanah'])
            ? PosisiTanah::from($data['posisi_tanah'])
            : null;

        $input->kondisi_tanah = isset($data['kondisi_tanah'])
            ? KondisiTanah::from($data['kondisi_tanah'])
            : null;

        $scored = $this->similarityService->findSimilar($input, $limit);

        if ($scored->isEmpty()) {
            return $this->success([], 'Tidak ada data pembanding yang cocok');
        }

        $eloquent = EloquentCollection::make($scored->all());
        $eloquent->load(['province', 'regency', 'district', 'village', 'creator']);

        $result = $eloquent->map(function ($item) {
            $res = (new PembandingResource($item))->toArray(request());
            $res['score'] = $item->score;
            $res['sql_distance'] = $item->sql_distance ?? null;
            return $res;
        });

        return $this->success($result, 'Beberapa data yang cocok', 200);
    }
}
