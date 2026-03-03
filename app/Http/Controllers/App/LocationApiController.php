<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use App\Services\Location\LocationIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class LocationApiController extends Controller
{
    /**
     * When a parent filter is present (province_id / regency_id / district_id),
     * return all matching children — a province has at most ~30 regencies,
     * a regency has at most ~30 districts, etc.
     *
     * When NO parent filter is given (i.e. a broad search), apply a safety cap
     * so we never accidentally stream tens of thousands of rows.
     */
    private const FILTERED_LIMIT   = 2000; // more than enough for any single parent
    private const UNFILTERED_LIMIT = 200;  // safety cap for broad / search-only calls

    public function __construct(private readonly LocationIdGenerator $generator) {}

    // ─── Province ─────────────────────────────────────────────────────────────

    public function provinces(): JsonResponse
    {
        $items = Province::query()->orderBy('id')->get(['id', 'name']);
        return response()->json($items);
    }

    public function storeProvince(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'   => ['required', 'string', 'size:2', Rule::unique('provinces', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $data['name'] = Str::upper(trim($data['name']));

        $province = Province::create($data);
        return response()->json($province, 201);
    }

    public function updateProvince(Request $request, string $province): JsonResponse
    {
        $record = Province::findOrFail($province);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $record->update($data);
        return response()->json($record);
    }

    public function deleteProvince(string $province): JsonResponse
    {
        Province::findOrFail($province)->delete();
        return response()->json(['success' => true]);
    }

    // ─── Regency ──────────────────────────────────────────────────────────────

    public function regencies(Request $request): JsonResponse
    {
        $hasParentFilter = $request->filled('province_id');
        $limit = $hasParentFilter ? self::FILTERED_LIMIT : self::UNFILTERED_LIMIT;

        // Honour an explicit per_page only when no parent filter — avoids abuse
        if (! $hasParentFilter && $request->filled('per_page')) {
            $limit = max(10, min(self::UNFILTERED_LIMIT, (int) $request->integer('per_page')));
        }

        $query = Regency::query()->with('province:id,name');

        if ($hasParentFilter) {
            $query->where('province_id', $request->get('province_id'));
        }

        if ($search = $request->get('q')) {
            $query->where('name', 'like', '%' . strtoupper($search) . '%');
        }

        $items = $query->orderBy('id')->limit($limit)->get(['id', 'name', 'province_id']);
        return response()->json($items);
    }

    public function storeRegency(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'string', 'size:2', Rule::exists('provinces', 'id')],
            'name'        => ['required', 'string', 'max:255'],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $regency = DB::transaction(function () use ($data) {
            $data['id'] = $this->generator->nextRegencyId((string) $data['province_id']);
            return Regency::create($data);
        });

        return response()->json($regency, 201);
    }

    public function updateRegency(Request $request, string $regency): JsonResponse
    {
        $record = Regency::findOrFail($regency);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'string', 'size:2', Rule::exists('provinces', 'id')],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $record->update($data);
        return response()->json($record);
    }

    public function deleteRegency(string $regency): JsonResponse
    {
        Regency::findOrFail($regency)->delete();
        return response()->json(['success' => true]);
    }

    // ─── District ─────────────────────────────────────────────────────────────

    public function districts(Request $request): JsonResponse
    {
        $hasParentFilter = $request->filled('regency_id');
        $limit = $hasParentFilter ? self::FILTERED_LIMIT : self::UNFILTERED_LIMIT;

        if (! $hasParentFilter && $request->filled('per_page')) {
            $limit = max(10, min(self::UNFILTERED_LIMIT, (int) $request->integer('per_page')));
        }

        $query = District::query()->with('regency:id,name,province_id');

        if ($hasParentFilter) {
            $query->where('regency_id', $request->get('regency_id'));
        }

        if ($search = $request->get('q')) {
            $query->where('name', 'like', '%' . strtoupper($search) . '%');
        }

        $items = $query->orderBy('id')->limit($limit)->get(['id', 'name', 'regency_id']);
        return response()->json($items);
    }

    public function storeDistrict(Request $request): JsonResponse
    {
        $data = $request->validate([
            'regency_id' => ['required', 'string', 'size:4', Rule::exists('regencies', 'id')],
            'name'       => ['required', 'string', 'max:255'],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $district = DB::transaction(function () use ($data) {
            $data['id'] = $this->generator->nextDistrictId((string) $data['regency_id']);
            return District::create($data);
        });

        return response()->json($district, 201);
    }

    public function updateDistrict(Request $request, string $district): JsonResponse
    {
        $record = District::findOrFail($district);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'regency_id' => ['required', 'string', 'size:4', Rule::exists('regencies', 'id')],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $record->update($data);
        return response()->json($record);
    }

    public function deleteDistrict(string $district): JsonResponse
    {
        District::findOrFail($district)->delete();
        return response()->json(['success' => true]);
    }

    // ─── Village ──────────────────────────────────────────────────────────────

    public function villages(Request $request): JsonResponse
    {
        $hasParentFilter = $request->filled('district_id');
        $limit = $hasParentFilter ? self::FILTERED_LIMIT : self::UNFILTERED_LIMIT;

        if (! $hasParentFilter && $request->filled('per_page')) {
            $limit = max(10, min(self::UNFILTERED_LIMIT, (int) $request->integer('per_page')));
        }

        $query = Village::query()->with('district:id,name,regency_id');

        if ($hasParentFilter) {
            $query->where('district_id', $request->get('district_id'));
        }

        if ($search = $request->get('q')) {
            $query->where('name', 'like', '%' . strtoupper($search) . '%');
        }

        $items = $query->orderBy('id')->limit($limit)->get(['id', 'name', 'district_id']);
        return response()->json($items);
    }

    public function storeVillage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'string', 'size:7', Rule::exists('districts', 'id')],
            'name'        => ['required', 'string', 'max:255'],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $village = DB::transaction(function () use ($data) {
            $data['id'] = $this->generator->nextVillageId((string) $data['district_id']);
            return Village::create($data);
        });

        return response()->json($village, 201);
    }

    public function updateVillage(Request $request, string $village): JsonResponse
    {
        $record = Village::findOrFail($village);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'string', 'size:7', Rule::exists('districts', 'id')],
        ]);
        $data['name'] = Str::upper(trim($data['name']));

        $record->update($data);
        return response()->json($record);
    }

    public function deleteVillage(string $village): JsonResponse
    {
        Village::findOrFail($village)->delete();
        return response()->json(['success' => true]);
    }
}
