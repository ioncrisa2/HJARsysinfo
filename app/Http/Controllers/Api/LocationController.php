<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Locations\DistrictSearchRequest;
use App\Http\Requests\Api\Locations\ProvinceSearchRequest;
use App\Http\Requests\Api\Locations\RegencySearchRequest;
use App\Http\Requests\Api\Locations\VillageSearchRequest;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Lokasi', 'Pencarian hierarki wilayah Indonesia.', weight: 3)]
class LocationController extends Controller
{
    use ApiResponse;

    private const DEFAULT_LIMIT = 50;

    private const MAX_LIMIT = 200;

    #[Endpoint(title: 'Cari provinsi', description: 'Mencari provinsi berdasarkan nama.')]
    public function provinces(ProvinceSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = Province::query()
            ->when($data['q'] ?? null, fn ($query, string $q) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get(['id', 'name']);

        return $this->success($items, 'Data Provinsi');
    }

    #[Endpoint(title: 'Cari kabupaten/kota', description: 'Mencari kabupaten atau kota, opsional dibatasi berdasarkan provinsi.')]
    public function regencies(RegencySearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = Regency::query();

        if ($data['province_id'] ?? null) {
            $query->where('province_id', $data['province_id']);
        }
        if ($data['q'] ?? null) {
            $query->where('name', 'like', "%{$data['q']}%");
        }

        $items = $query->orderBy('name')
            ->limit($this->limit($request))
            ->get(['id', 'name', 'province_id']);

        return $this->success($items, 'Data Kabupaten/Kota');
    }

    #[Endpoint(title: 'Cari kecamatan', description: 'Mencari kecamatan, opsional dibatasi berdasarkan kabupaten/kota.')]
    public function districts(DistrictSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = District::query();

        if ($data['regency_id'] ?? null) {
            $query->where('regency_id', $data['regency_id']);
        }
        if ($data['q'] ?? null) {
            $query->where('name', 'like', "%{$data['q']}%");
        }

        $items = $query->orderBy('name')
            ->limit($this->limit($request))
            ->get(['id', 'name', 'regency_id']);

        return $this->success($items, 'Data Kecamatan');
    }

    #[Endpoint(title: 'Cari desa/kelurahan', description: 'Mencari desa atau kelurahan, opsional dibatasi berdasarkan kecamatan.')]
    public function villages(VillageSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = Village::query();

        if ($data['district_id'] ?? null) {
            $query->where('district_id', $data['district_id']);
        }
        if ($data['q'] ?? null) {
            $query->where('name', 'like', "%{$data['q']}%");
        }

        $items = $query->orderBy('name')
            ->limit($this->limit($request))
            ->get(['id', 'name', 'district_id']);

        return $this->success($items, 'Data Desa/Kelurahan');
    }

    private function limit(Request $request): int
    {
        return min(
            (int) $request->integer('limit', self::DEFAULT_LIMIT),
            self::MAX_LIMIT
        );
    }
}
