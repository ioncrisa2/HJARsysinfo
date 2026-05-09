<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Services\Location\LocationIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class GeoDataController extends Controller
{
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

    public function __construct(private readonly LocationIdGenerator $idGenerator)
    {
    }

    public function index(Request $request, ?string $resource = null): Response
    {
        if ($resource !== null && ! array_key_exists($resource, self::RESOURCE_MAP)) {
            abort(404);
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 10), 100);
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province_id' => $request->query('province_id'),
            'regency_id' => $request->query('regency_id'),
            'district_id' => $request->query('district_id'),
            'per_page' => $perPage,
        ];

        return Inertia::render('Admin/GeoData/Index', [
            'title' => $resource ? self::RESOURCE_MAP[$resource]['label'] : 'Data Lokasi',
            'currentResource' => $resource,
            'resources' => $this->resources(),
            'records' => $resource
                ? $this->recordsFor($resource, $filters, $perPage)
                : ['data' => [], 'links' => []],
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => $this->optionsFor($filters),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        abort_unless(array_key_exists($resource, self::RESOURCE_MAP), 404);

        $record = match ($resource) {
            'provinces' => Province::create($this->validateProvince($request)),
            'regencies' => $this->createRegency($request),
            'districts' => $this->createDistrict($request),
            'villages' => $this->createVillage($request),
        };

        return redirect()
            ->route('admin.geo.show', ['resource' => $resource])
            ->with('success', "{$record->name} berhasil ditambahkan.");
    }

    public function update(Request $request, string $resource, string $id): RedirectResponse
    {
        abort_unless(array_key_exists($resource, self::RESOURCE_MAP), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $record = $this->findRecord($resource, $id);
        $record->update(['name' => $this->normalizeName($data['name'])]);

        return redirect()
            ->back()
            ->with('success', "{$record->name} berhasil diperbarui.");
    }

    public function destroy(string $resource, string $id): RedirectResponse
    {
        abort_unless(array_key_exists($resource, self::RESOURCE_MAP), 404);

        $record = $this->findRecord($resource, $id);
        $name = $record->name;

        try {
            $record->delete();

            return redirect()
                ->back()
                ->with('success', "{$name} berhasil dihapus.");
        } catch (Throwable) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus data lokasi. Data ini mungkin masih digunakan.');
        }
    }

    public function regencies(Request $request): JsonResponse
    {
        $request->validate([
            'province_id' => ['required', 'string', 'size:2', Rule::exists('provinces', 'id')],
        ]);

        return response()->json(
            Regency::query()
                ->where('province_id', (string) $request->string('province_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'province_id'])
        );
    }

    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'regency_id' => ['required', 'string', 'size:4', Rule::exists('regencies', 'id')],
        ]);

        return response()->json(
            District::query()
                ->where('regency_id', (string) $request->string('regency_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'regency_id'])
        );
    }

    private function recordsFor(string $resource, array $filters, int $perPage)
    {
        $search = $filters['search'];

        $query = match ($resource) {
            'provinces' => Province::query()->withCount('regencies'),
            'regencies' => Regency::query()->with('province:id,name')->withCount('districts'),
            'districts' => District::query()->with('regency.province:id,name')->withCount('villages'),
            'villages' => Village::query()->with('district.regency.province:id,name'),
        };

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', '%' . Str::upper($search) . '%');
            });
        }

        if ($resource === 'regencies' && filled($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if ($resource === 'districts') {
            if (filled($filters['province_id'])) {
                $query->whereHas('regency', fn ($query) => $query->where('province_id', $filters['province_id']));
            }

            if (filled($filters['regency_id'])) {
                $query->where('regency_id', $filters['regency_id']);
            }
        }

        if ($resource === 'villages') {
            if (filled($filters['province_id'])) {
                $query->whereHas(
                    'district.regency',
                    fn ($query) => $query->where('province_id', $filters['province_id'])
                );
            }

            if (filled($filters['regency_id'])) {
                $query->whereHas('district', fn ($query) => $query->where('regency_id', $filters['regency_id']));
            }

            if (filled($filters['district_id'])) {
                $query->where('district_id', $filters['district_id']);
            }
        }

        return $query
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function validateProvince(Request $request): array
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'size:2', Rule::unique('provinces', 'id')],
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
            return Regency::create([
                'id' => $this->idGenerator->nextRegencyId($data['province_id']),
                'province_id' => $data['province_id'],
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
            return District::create([
                'id' => $this->idGenerator->nextDistrictId($data['regency_id']),
                'regency_id' => $data['regency_id'],
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
            return Village::create([
                'id' => $this->idGenerator->nextVillageId($data['district_id']),
                'district_id' => $data['district_id'],
                'name' => $this->normalizeName($data['name']),
            ]);
        });
    }

    private function findRecord(string $resource, string $id): Province|Regency|District|Village
    {
        return match ($resource) {
            'provinces' => Province::findOrFail($id),
            'regencies' => Regency::findOrFail($id),
            'districts' => District::findOrFail($id),
            'villages' => Village::findOrFail($id),
        };
    }

    private function normalizeName(string $name): string
    {
        return Str::upper(trim($name));
    }

    private function resources(): array
    {
        return collect(self::RESOURCE_MAP)
            ->map(fn (array $meta, string $slug): array => [
                'slug' => $slug,
                ...$meta,
            ])
            ->values()
            ->all();
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

    private function optionsFor(array $filters): array
    {
        return [
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'regencies' => filled($filters['province_id'])
                ? Regency::query()
                    ->where('province_id', $filters['province_id'])
                    ->orderBy('name')
                    ->get(['id', 'name', 'province_id'])
                : [],
            'districts' => filled($filters['regency_id'])
                ? District::query()
                    ->where('regency_id', $filters['regency_id'])
                    ->orderBy('name')
                    ->get(['id', 'name', 'regency_id'])
                : [],
        ];
    }
}
