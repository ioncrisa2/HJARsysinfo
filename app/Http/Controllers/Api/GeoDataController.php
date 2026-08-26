<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Services\Location\LocationIdGenerator;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

#[Group('Geo Administration', 'Manajemen data wilayah administratif (Provinsi, Kabupaten/Kota, Kecamatan, Desa/Kelurahan).', weight: 5)]
class GeoDataController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    private const RESOURCE_MAP = [
        'provinces' => [
            'label' => 'Provinsi',
            'singular' => 'Provinsi',
            'icon' => 'pi pi-flag',
            'id_label' => 'Kode Provinsi',
            'id_help' => 'Kode 2 digit diisi manual.',
            'children_label' => 'Kabupaten / Kota',
        ],
        'regencies' => [
            'label' => 'Kabupaten / Kota',
            'singular' => 'Kabupaten / Kota',
            'icon' => 'pi pi-building',
            'id_label' => 'Kode Kabupaten / Kota',
            'id_help' => 'Kode dibuat otomatis dari provinsi.',
            'parent_label' => 'Provinsi',
            'parent_key' => 'province_id',
            'children_label' => 'Kecamatan',
        ],
        'districts' => [
            'label' => 'Kecamatan',
            'singular' => 'Kecamatan',
            'icon' => 'pi pi-map-marker',
            'id_label' => 'Kode Kecamatan',
            'id_help' => 'Kode dibuat otomatis dari kabupaten / kota.',
            'parent_label' => 'Kabupaten / Kota',
            'parent_key' => 'regency_id',
            'children_label' => 'Desa / Kelurahan',
        ],
        'villages' => [
            'label' => 'Desa / Kelurahan',
            'singular' => 'Desa / Kelurahan',
            'icon' => 'pi pi-home',
            'id_label' => 'Kode Desa / Kelurahan',
            'id_help' => 'Kode dibuat otomatis dari kecamatan.',
            'parent_label' => 'Kecamatan',
            'parent_key' => 'district_id',
            'children_label' => null,
        ],
    ];

    public function __construct(private readonly LocationIdGenerator $idGenerator) {}

    #[Endpoint(
        title: 'Lihat daftar data geo administratif',
        description: 'Mengembalikan daftar wilayah administratif terpaginasi (provinces, regencies, districts, atau villages) beserta filter pencarian dan relasi induk.'
    )]
    #[PathParameter('resource', description: 'Tipe wilayah administratif.', type: "'provinces'|'regencies'|'districts'|'villages'", example: 'provinces')]
    public function index(Request $request, ?string $resource = null): JsonResponse
    {
        $this->authorizePermission('view_geo_data');

        if ($resource !== null && ! array_key_exists($resource, self::RESOURCE_MAP)) {
            return $this->notFound('Tipe resource wilayah tidak ditemukan.');
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 10), 100);
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province_id' => $request->query('province_id'),
            'regency_id' => $request->query('regency_id'),
            'district_id' => $request->query('district_id'),
            'per_page' => $perPage,
        ];

        if (! $resource) {
            return $this->success([
                'resources' => $this->resources(),
                'stats' => $this->stats(),
            ], 'Metadata geo berhasil diambil.');
        }

        $records = $this->recordsFor($resource, $filters, $perPage);

        return response()->json([
            'status' => 'success',
            'message' => "Data {$resource} berhasil diambil.",
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
            'links' => [
                'first' => $records->url(1),
                'last' => $records->url($records->lastPage()),
                'prev' => $records->previousPageUrl(),
                'next' => $records->nextPageUrl(),
            ],
            'resource_meta' => self::RESOURCE_MAP[$resource] ?? null,
            'stats' => $this->stats(),
            'options' => $this->optionsFor($filters),
        ]);
    }

    #[Endpoint(
        title: 'Tambah data geo administratif',
        description: 'Menambahkan data wilayah administratif baru.'
    )]
    public function store(Request $request, string $resource): JsonResponse
    {
        $this->authorizePermission('create_geo_data');

        if (! array_key_exists($resource, self::RESOURCE_MAP)) {
            return $this->notFound('Resource tidak valid.');
        }

        $record = match ($resource) {
            'provinces' => Province::create($this->validateProvince($request)),
            'regencies' => $this->createRegency($request),
            'districts' => $this->createDistrict($request),
            'villages' => $this->createVillage($request),
        };

        return $this->success($record, "{$record->name} berhasil ditambahkan.", 201);
    }

    #[Endpoint(
        title: 'Ubah nama wilayah geo administratif',
        description: 'Memperbarui nama wilayah administratif yang ditentukan.'
    )]
    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        $this->authorizePermission('update_geo_data');

        if (! array_key_exists($resource, self::RESOURCE_MAP)) {
            return $this->notFound('Resource tidak valid.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $record = $this->findRecord($resource, $id);
        $record->update(['name' => $this->normalizeName($data['name'])]);

        return $this->success($record, "{$record->name} berhasil diperbarui.");
    }

    #[Endpoint(
        title: 'Hapus wilayah geo administratif',
        description: 'Menghapus data wilayah administratif jika tidak digunakan.'
    )]
    public function destroy(string $resource, string $id): JsonResponse
    {
        $this->authorizePermission('delete_geo_data');

        if (! array_key_exists($resource, self::RESOURCE_MAP)) {
            return $this->notFound('Resource tidak valid.');
        }

        $record = $this->findRecord($resource, $id);
        $name = $record->name;

        try {
            $record->delete();

            return $this->success(null, "{$name} berhasil dihapus.");
        } catch (Throwable) {
            return $this->error('Gagal menghapus data lokasi. Data ini mungkin masih digunakan.', 422, null, 'DELETE_FAILED');
        }
    }

    private function resources(): array
    {
        return collect(self::RESOURCE_MAP)->map(fn (array $meta, string $key): array => [
            'key' => $key,
            'label' => $meta['label'],
            'singular' => $meta['singular'],
            'icon' => $meta['icon'],
            'children_label' => $meta['children_label'],
            'parent_key' => $meta['parent_key'] ?? null,
        ])->values()->all();
    }

    private function stats(): array
    {
        return [
            'provinces' => Province::count(),
            'regencies' => Regency::count(),
            'districts' => District::count(),
            'villages' => Village::count(),
        ];
    }

    private function recordsFor(string $resource, array $filters, int $perPage)
    {
        $search = $filters['search'];

        return match ($resource) {
            'provinces' => Province::query()
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "{$search}%"))
                ->withCount('regencies')
                ->orderBy('name')
                ->paginate($perPage)
                ->through(fn (Province $p): array => [
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'children_count' => $p->regencies_count,
                ]),
            'regencies' => Regency::query()
                ->when($filters['province_id'], fn ($q, $id) => $q->where('province_id', $id))
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "{$search}%"))
                ->with('province:id,name')
                ->withCount('districts')
                ->orderBy('name')
                ->paginate($perPage)
                ->through(fn (Regency $r): array => [
                    'id' => (string) $r->id,
                    'name' => $r->name,
                    'province_id' => (string) $r->province_id,
                    'parent_name' => $r->province?->name,
                    'children_count' => $r->districts_count,
                ]),
            'districts' => District::query()
                ->when($filters['regency_id'], fn ($q, $id) => $q->where('regency_id', $id))
                ->when($filters['province_id'] && ! $filters['regency_id'], fn ($q) => $q->whereHas('regency', fn ($rq) => $rq->where('province_id', $filters['province_id'])))
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "{$search}%"))
                ->with(['regency.province:id,name'])
                ->withCount('villages')
                ->orderBy('name')
                ->paginate($perPage)
                ->through(fn (District $d): array => [
                    'id' => (string) $d->id,
                    'name' => $d->name,
                    'regency_id' => (string) $d->regency_id,
                    'province_id' => (string) optional($d->regency)->province_id,
                    'parent_name' => $d->regency ? "{$d->regency->name}, {$d->regency->province?->name}" : null,
                    'children_count' => $d->villages_count,
                ]),
            'villages' => Village::query()
                ->when($filters['district_id'], fn ($q, $id) => $q->where('district_id', $id))
                ->when($filters['regency_id'] && ! $filters['district_id'], fn ($q) => $q->whereHas('district', fn ($dq) => $dq->where('regency_id', $filters['regency_id'])))
                ->when($filters['province_id'] && ! $filters['regency_id'] && ! $filters['district_id'], fn ($q) => $q->whereHas('district.regency', fn ($rq) => $rq->where('province_id', $filters['province_id'])))
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "{$search}%"))
                ->with(['district.regency.province:id,name'])
                ->orderBy('name')
                ->paginate($perPage)
                ->through(fn (Village $v): array => [
                    'id' => (string) $v->id,
                    'name' => $v->name,
                    'district_id' => (string) $v->district_id,
                    'regency_id' => (string) optional($v->district)->regency_id,
                    'province_id' => (string) optional(optional($v->district)->regency)->province_id,
                    'parent_name' => $v->district ? "{$v->district->name}, ".optional($v->district->regency)->name : null,
                    'children_count' => 0,
                ]),
            default => abort(404),
        };
    }

    private function optionsFor(array $filters): array
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Province $p): array => ['value' => (string) $p->id, 'label' => $p->name]);

        $regencies = collect();
        if ($filters['province_id']) {
            $regencies = Regency::query()
                ->where('province_id', $filters['province_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Regency $r): array => ['value' => (string) $r->id, 'label' => $r->name]);
        }

        $districts = collect();
        if ($filters['regency_id']) {
            $districts = District::query()
                ->where('regency_id', $filters['regency_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (District $d): array => ['value' => (string) $d->id, 'label' => $d->name]);
        }

        return [
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
        ];
    }

    private function validateProvince(Request $request): array
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'size:2', 'regex:/^\d{2}$/', 'unique:provinces,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        return [
            'id' => $data['id'],
            'name' => $this->normalizeName($data['name']),
        ];
    }

    private function createRegency(Request $request): Regency
    {
        $data = $request->validate([
            'province_id' => ['required', 'string', 'size:2', Rule::exists('provinces', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data): Regency {
            $province = Province::query()->whereKey($data['province_id'])->lockForUpdate()->firstOrFail();

            return Regency::create([
                'id' => $this->idGenerator->nextRegencyId($province->id),
                'province_id' => $province->id,
                'name' => $this->normalizeName($data['name']),
            ]);
        });
    }

    private function createDistrict(Request $request): District
    {
        $data = $request->validate([
            'regency_id' => ['required', 'string', 'size:4', Rule::exists('regencies', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data): District {
            $regency = Regency::query()->whereKey($data['regency_id'])->lockForUpdate()->firstOrFail();

            return District::create([
                'id' => $this->idGenerator->nextDistrictId($regency->id),
                'regency_id' => $regency->id,
                'name' => $this->normalizeName($data['name']),
            ]);
        });
    }

    private function createVillage(Request $request): Village
    {
        $data = $request->validate([
            'district_id' => ['required', 'string', 'size:7', Rule::exists('districts', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data): Village {
            $district = District::query()->whereKey($data['district_id'])->lockForUpdate()->firstOrFail();

            return Village::create([
                'id' => $this->idGenerator->nextVillageId($district->id),
                'district_id' => $district->id,
                'name' => $this->normalizeName($data['name']),
            ]);
        });
    }

    private function findRecord(string $resource, string $id)
    {
        return match ($resource) {
            'provinces' => Province::findOrFail($id),
            'regencies' => Regency::findOrFail($id),
            'districts' => District::findOrFail($id),
            'villages' => Village::findOrFail($id),
            default => abort(404),
        };
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->squish()
            ->trim()
            ->upper()
            ->toString();
    }
}
