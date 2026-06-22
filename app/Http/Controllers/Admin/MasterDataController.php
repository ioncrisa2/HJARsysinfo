<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminPermissions;
use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MasterDataController extends Controller
{
    use AuthorizesAdminPermissions;

    private array $resourceMap = [
        'bentuk-tanah'              => \App\Models\BentukTanah::class,
        'dokumen-tanah'             => \App\Models\DokumenTanah::class,
        'jenis-listing'             => \App\Models\JenisListing::class,
        'jenis-objek'               => \App\Models\JenisObjek::class,
        'kondisi-tanah'             => \App\Models\KondisiTanah::class,
        'peruntukan'                => \App\Models\Peruntukan::class,
        'posisi-tanah'              => \App\Models\PosisiTanah::class,
        'status-pemberi-informasi'  => \App\Models\StatusPemberiInformasi::class,
        'topografi'                 => \App\Models\Topografi::class,
    ];

    public function index(Request $request, ?string $resource = null): Response
    {
        $this->authorizeAdmin('view_master_data');

        if (!$resource || !isset($this->resourceMap[$resource])) {
            return Inertia::render('Admin/MasterData/Index', [
                'currentResource' => null,
                'label'           => 'Master Data',
                'records'         => ['data' => [], 'links' => []],
                'filters'         => $this->filters($request),
                'resources'       => $this->getAvailableResources(),
                'can'             => $this->capabilities($request),
            ]);
        }

        $modelClass = $this->resourceMap[$resource];
        $label = Str::title(str_replace('-', ' ', $resource));
        $filters = $this->filters($request);

        $query = $modelClass::query();

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        }

        if ($filters['status'] === 'inactive') {
            $query->where('is_active', false);
        }

        $records = $query
            ->orderBy($filters['sort_by'], $filters['sort_dir'])
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/MasterData/Index', [
            'currentResource' => $resource,
            'label'           => $label,
            'records'         => $records,
            'resources'       => $this->getAvailableResources(),
            'filters'         => $filters,
            'supportsBadgeColor' => $this->supportsBadgeColor($modelClass),
            'can' => $this->capabilities($request),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeAdmin('create_master_data');

        $modelClass = $this->modelClass($resource);

        $data = $this->validatedData($request, $modelClass);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name']);
        $data['sort_order'] ??= ((int) $modelClass::query()->max('sort_order')) + 1;

        $modelClass::create($data);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, string $resource, $id): RedirectResponse
    {
        $this->authorizeAdmin('update_master_data');

        $modelClass = $this->modelClass($resource);
        $record = $modelClass::findOrFail($id);

        $data = $this->validatedData($request, $modelClass);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name'], $record->getKey());

        $record->update($data);

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(string $resource, $id): RedirectResponse
    {
        $this->authorizeAdmin('delete_master_data');

        $modelClass = $this->modelClass($resource);
        $record = $modelClass::findOrFail($id);

        try {
            $record->delete();
            return redirect()->back()->with('success', 'Data berhasil dihapus.');
        } catch (Throwable) {
            return redirect()->back()->with('error', 'Gagal menghapus data. Data ini mungkin masih digunakan oleh record lain.');
        }
    }

    public function toggleStatus(string $resource, $id): RedirectResponse
    {
        $this->authorizeAdmin('update_master_data_status');

        $modelClass = $this->modelClass($resource);
        $record = $modelClass::findOrFail($id);

        $record->update(['is_active' => ! $record->is_active]);

        return redirect()->back()->with('success', 'Status data berhasil diperbarui.');
    }

    public function reorder(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeAdmin('reorder_master_data');

        $modelClass = $this->modelClass($resource);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
            'start_order' => ['nullable', 'integer', 'min:1'],
        ]);
        $startOrder = (int) ($data['start_order'] ?? 1);

        DB::transaction(function () use ($modelClass, $data, $startOrder): void {
            foreach (array_values($data['ids']) as $index => $id) {
                $modelClass::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $startOrder + $index]);
            }
        });

        return redirect()->back()->with('success', 'Urutan data berhasil disimpan.');
    }

    public function bulkDestroy(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeAdmin('delete_any_master_data');

        $modelClass = $this->modelClass($resource);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        try {
            $deleted = $modelClass::query()
                ->whereKey($data['ids'])
                ->delete();

            return redirect()->back()->with('success', "{$deleted} data berhasil dihapus.");
        } catch (Throwable) {
            return redirect()->back()->with('error', 'Gagal menghapus sebagian data. Data mungkin masih digunakan oleh record lain.');
        }
    }

    private function filters(Request $request): array
    {
        $sortBy = $request->query('sort_by', 'sort_order');
        $sortDir = $request->query('sort_dir', 'asc');
        $status = $request->query('status', 'all');

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
            'sort_by' => in_array($sortBy, ['sort_order', 'name', 'slug', 'is_active'], true) ? $sortBy : 'sort_order',
            'sort_dir' => $sortDir === 'desc' ? 'desc' : 'asc',
            'per_page' => min(max((int) $request->integer('per_page', 20), 10), 100),
        ];
    }

    private function capabilities(Request $request): array
    {
        return AdminAccess::capabilityMap($request->user(), [
            'create' => 'create_master_data',
            'update' => 'update_master_data',
            'toggleStatus' => 'update_master_data_status',
            'delete' => 'delete_master_data',
            'deleteAny' => 'delete_any_master_data',
            'reorder' => 'reorder_master_data',
        ]);
    }

    private function validatedData(Request $request, string $modelClass): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
            'badge_color' => ['nullable', 'string', 'max:20'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if (! $this->supportsBadgeColor($modelClass)) {
            unset($data['badge_color']);
        }

        return $data;
    }

    private function uniqueSlug(string $modelClass, string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (
            $modelClass::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function modelClass(string $resource): string
    {
        abort_unless(isset($this->resourceMap[$resource]), 404);

        return $this->resourceMap[$resource];
    }

    private function supportsBadgeColor(string $modelClass): bool
    {
        return Schema::hasColumn((new $modelClass())->getTable(), 'badge_color');
    }

    private function getAvailableResources(): array
    {
        return collect($this->resourceMap)->map(function ($class, $slug) {
            return [
                'slug'  => $slug,
                'label' => Str::title(str_replace('-', ' ', $slug)),
                'icon'  => $this->getIconForResource($slug),
                'supportsBadgeColor' => $this->supportsBadgeColor($class),
            ];
        })->values()->all();
    }

    private function getIconForResource(string $slug): string
    {
        return match ($slug) {
            'bentuk-tanah'             => 'pi pi-box',
            'dokumen-tanah'            => 'pi pi-file-pdf',
            'jenis-listing'            => 'pi pi-tag',
            'jenis-objek'              => 'pi pi-building',
            'kondisi-tanah'            => 'pi pi-map',
            'peruntukan'               => 'pi pi-map-marker',
            'posisi-tanah'             => 'pi pi-compass',
            'status-pemberi-informasi' => 'pi pi-user',
            'topografi'                => 'pi pi-chart-line',
            default                    => 'pi pi-database',
        };
    }
}
