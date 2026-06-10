<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    public function provinces(): JsonResponse
    {
        $items = Province::query()->orderBy('name')->get(['id', 'name']);
        return $this->success($items, 'Data Provinsi');
    }

    public function regencies(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');
        $q = $request->query('q');

        $query = Regency::query();

        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }
        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->orderBy('name')->get(['id', 'name', 'province_id']);

        return $this->success($items, 'Data Kabupaten/Kota');
    }

    public function districts(Request $request): JsonResponse
    {
        $regencyId = $request->query('regency_id');
        $q = $request->query('q');

        $query = District::query();

        if ($regencyId) {
            $query->where('regency_id', $regencyId);
        }
        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->orderBy('name')->get(['id', 'name', 'regency_id']);

        return $this->success($items, 'Data Kecamatan');
    }

    public function villages(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');
        $q = $request->query('q');

        $query = Village::query();

        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->orderBy('name')->get(['id', 'name', 'district_id']);

        return $this->success($items, 'Data Desa/Kelurahan');
    }
}
