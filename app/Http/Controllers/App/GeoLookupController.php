<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoLookupController extends Controller
{
    public function regencies(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');

        $items = $provinceId
            ? Regency::query()->where('province_id', $provinceId)->orderBy('name')->get(['id', 'name'])
            : collect();

        return response()->json($this->map($items));
    }

    public function districts(Request $request): JsonResponse
    {
        $regencyId = $request->query('regency_id');

        $items = $regencyId
            ? District::query()->where('regency_id', $regencyId)->orderBy('name')->get(['id', 'name'])
            : collect();

        return response()->json($this->map($items));
    }

    public function villages(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');

        $items = $districtId
            ? Village::query()->where('district_id', $districtId)->orderBy('name')->get(['id', 'name'])
            : collect();

        return response()->json($this->map($items));
    }

    private function map($items): array
    {
        return $items->map(fn ($item) => ['label' => $item->name, 'value' => $item->id])->values()->all();
    }
}
