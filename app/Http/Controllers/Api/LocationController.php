<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    private const DEFAULT_LIMIT = 50;

    private const MAX_LIMIT = 200;

    public function provinces(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

        $items = Province::query()
            ->when($data['q'] ?? null, fn ($query, string $q) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get(['id', 'name']);

        return $this->success($items, 'Data Provinsi');
    }

    public function regencies(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

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

    public function districts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'regency_id' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

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

    public function villages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

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
