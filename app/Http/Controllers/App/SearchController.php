<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Support\AppAccess;
use App\Supports\DictionaryTypeMap;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    use AuthorizesPermissions;

    private const PER_RESOURCE_LIMIT = 40;

    private array $geoResources = [
        'provinces' => [Province::class, 'Provinsi'],
        'regencies' => [Regency::class, 'Kabupaten / Kota'],
        'districts' => [District::class, 'Kecamatan'],
        'villages' => [Village::class, 'Desa / Kelurahan'],
    ];

    public function __invoke(): Response
    {
        $this->authorizePermission('view_search');

        $filters = $this->filters();
        $rawResults = $filters['q'] !== '' ? $this->search($filters['q']) : collect();
        $filteredResults = $this->applyResultFilters($rawResults, $filters);
        $paginated = $this->paginate($filteredResults, $filters);

        return Inertia::render('Search/Index', [
            'query' => $filters['q'],
            'filters' => $filters,
            'results' => $paginated,
            'summary' => [
                'raw_total' => $rawResults->count(),
                'filtered_total' => $filteredResults->count(),
            ],
            'options' => [
                'menuGroups' => $this->optionValues($rawResults, 'menu_group'),
                'menuNames' => $this->optionValues(
                    $filters['menu_group'] !== ''
                        ? $rawResults->where('menu_group', $filters['menu_group'])
                        : $rawResults,
                    'menu_name'
                ),
                'resourceNames' => $this->optionValues(
                    $rawResults
                        ->when($filters['menu_group'] !== '', fn (Collection $results) => $results->where('menu_group', $filters['menu_group']))
                        ->when($filters['menu_name'] !== '', fn (Collection $results) => $results->where('menu_name', $filters['menu_name'])),
                    'resource_name'
                ),
            ],
        ]);
    }

    private function filters(): array
    {
        $data = request()->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'menu_group' => ['nullable', 'string', 'max:100'],
            'menu_name' => ['nullable', 'string', 'max:100'],
            'resource_name' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'menu_group' => trim((string) ($data['menu_group'] ?? '')),
            'menu_name' => trim((string) ($data['menu_name'] ?? '')),
            'resource_name' => trim((string) ($data['resource_name'] ?? '')),
            'per_page' => (int) ($data['per_page'] ?? 15),
            'page' => (int) ($data['page'] ?? 1),
        ];
    }

    private function search(string $keyword): Collection
    {
        return collect()
            ->merge($this->searchUsers($keyword))
            ->merge($this->searchPembanding($keyword))
            ->merge($this->searchModeration($keyword))
            ->merge($this->searchMasterData($keyword))
            ->merge($this->searchGeoData($keyword))
            ->sortBy(fn (array $row): string => Str::lower(
                "{$row['menu_group']}|{$row['menu_name']}|{$row['resource_name']}|{$row['title']}"
            ))
            ->values();
    }

    private function searchUsers(string $keyword): Collection
    {
        if (! AppAccess::can(request()->user(), 'view_any_user')) {
            return collect();
        }

        return User::query()
            ->with('roles:id,name')
            ->where(function ($query) use ($keyword): void {
                $query
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->latest()
            ->limit(self::PER_RESOURCE_LIMIT)
            ->get()
            ->map(fn (User $user): array => $this->resultRow(
                menuGroup: 'Manajemen',
                menuName: 'Users Management',
                resourceName: 'User',
                title: $user->name,
                url: "/app/users/{$user->id}/edit",
                details: [
                    'Email' => $user->email,
                    'Roles' => $user->roles->pluck('name')->implode(', '),
                    'Status' => $user->deactivated_at ? 'Inactive' : 'Active',
                ],
                icon: 'pi pi-user'
            ));
    }

    private function searchPembanding(string $keyword): Collection
    {
        if (! AppAccess::can(request()->user(), 'view_any_data::pembanding')) {
            return collect();
        }

        return Pembanding::query()
            ->with(['jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name', 'regency:id,name'])
            ->where(function ($query) use ($keyword): void {
                $query
                    ->when(ctype_digit($keyword), fn ($query) => $query->where('id', (int) $keyword))
                    ->orWhere('alamat_data', 'like', "%{$keyword}%")
                    ->orWhere('nama_pemberi_informasi', 'like', "%{$keyword}%")
                    ->orWhere('nomer_telepon_pemberi_informasi', 'like', "%{$keyword}%");
            })
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->limit(self::PER_RESOURCE_LIMIT)
            ->get()
            ->map(fn (Pembanding $record): array => $this->resultRow(
                menuGroup: 'Bank Data',
                menuName: 'Appraisal Data',
                resourceName: 'Data Pembanding',
                title: $record->alamat_data ?: "Data Pembanding #{$record->id}",
                url: "/app/pembanding/{$record->id}",
                details: [
                    'ID' => "#{$record->id}",
                    'Pemberi Info' => $record->nama_pemberi_informasi,
                    'Tipe' => collect([$record->jenisListing?->name, $record->jenisObjek?->name])->filter()->implode(' / '),
                    'Lokasi' => collect([$record->regency?->name, $record->province?->name])->filter()->implode(', '),
                ],
                icon: 'pi pi-database'
            ));
    }

    private function searchModeration(string $keyword): Collection
    {
        if (! AppAccess::can(request()->user(), 'view_moderation')) {
            return collect();
        }

        $requests = PembandingDeleteRequest::query()
            ->with(['pembanding:id,alamat_data', 'requestedBy:id,name'])
            ->where(function ($query) use ($keyword): void {
                $query
                    ->when(ctype_digit($keyword), fn ($query) => $query->where('id', (int) $keyword))
                    ->orWhere('reason', 'like', "%{$keyword}%")
                    ->orWhere('status', 'like', "%{$keyword}%")
                    ->orWhereHas('pembanding', fn ($query) => $query->where('alamat_data', 'like', "%{$keyword}%"))
                    ->orWhereHas('requestedBy', fn ($query) => $query->where('name', 'like', "%{$keyword}%"));
            })
            ->latest()
            ->limit(self::PER_RESOURCE_LIMIT)
            ->get()
            ->map(fn (PembandingDeleteRequest $request): array => $this->resultRow(
                menuGroup: 'Manajemen',
                menuName: 'Moderation Desk',
                resourceName: 'Delete Request',
                title: $request->pembanding?->alamat_data ?: "Delete Request #{$request->id}",
                url: "/app/moderation?tab=requests&search={$request->id}",
                details: [
                    'Status' => $request->status,
                    'Requested By' => $request->requestedBy?->name,
                    'Reason' => Str::limit((string) $request->reason, 120),
                ],
                icon: 'pi pi-shield'
            ));

        $trashed = Pembanding::onlyTrashed()
            ->with('deletedBy:id,name')
            ->where(function ($query) use ($keyword): void {
                $query
                    ->when(ctype_digit($keyword), fn ($query) => $query->where('id', (int) $keyword))
                    ->orWhere('alamat_data', 'like', "%{$keyword}%")
                    ->orWhere('deleted_reason', 'like', "%{$keyword}%");
            })
            ->latest('deleted_at')
            ->limit(self::PER_RESOURCE_LIMIT)
            ->get()
            ->map(fn (Pembanding $record): array => $this->resultRow(
                menuGroup: 'Manajemen',
                menuName: 'Moderation Desk',
                resourceName: 'Trashed Data',
                title: $record->alamat_data ?: "Trashed Data #{$record->id}",
                url: "/app/moderation?tab=trashed&search={$record->id}",
                details: [
                    'Deleted By' => $record->deletedBy?->name,
                    'Reason' => Str::limit((string) $record->deleted_reason, 120),
                    'Deleted At' => optional($record->deleted_at)->format('Y-m-d H:i'),
                ],
                icon: 'pi pi-trash'
            ));

        return $requests->merge($trashed);
    }

    private function searchMasterData(string $keyword): Collection
    {
        if (! AppAccess::can(request()->user(), 'view_master_data')) {
            return collect();
        }

        $results = collect();

        foreach (DictionaryTypeMap::definitions() as $definition) {
            $slug = $definition['type'];
            $modelClass = $definition['model'];
            $label = $definition['label'];
            $results = $results->merge(
                $modelClass::query()
                    ->where(function ($query) use ($keyword): void {
                        $query
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('slug', 'like', "%{$keyword}%");
                    })
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(self::PER_RESOURCE_LIMIT)
                    ->get()
                    ->map(fn ($record): array => $this->resultRow(
                        menuGroup: 'Reference Data',
                        menuName: 'Master Data',
                        resourceName: $label,
                        title: $record->name,
                        url: "/app/master-data/{$slug}?search=".urlencode((string) $record->name),
                        details: [
                            'Slug' => $record->slug,
                            'Status' => $record->is_active ? 'Active' : 'Inactive',
                            'Sort Order' => (string) $record->sort_order,
                        ],
                        icon: 'pi pi-box'
                    ))
            );
        }

        return $results;
    }

    private function searchGeoData(string $keyword): Collection
    {
        if (! AppAccess::can(request()->user(), 'view_geo_data')) {
            return collect();
        }

        $results = collect();

        foreach ($this->geoResources as $slug => [$modelClass, $label]) {
            $results = $results->merge(
                $modelClass::query()
                    ->where(function ($query) use ($keyword): void {
                        $query
                            ->where('id', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', '%'.Str::upper($keyword).'%');
                    })
                    ->orderBy('id')
                    ->limit(self::PER_RESOURCE_LIMIT)
                    ->get()
                    ->map(fn ($record): array => $this->resultRow(
                        menuGroup: 'Reference Data',
                        menuName: 'Geo Location',
                        resourceName: $label,
                        title: $record->name,
                        url: "/app/geo/{$slug}?search=".urlencode((string) $record->id),
                        details: [
                            'Kode' => (string) $record->id,
                        ],
                        icon: 'pi pi-map'
                    ))
            );
        }

        return $results;
    }

    private function applyResultFilters(Collection $results, array $filters): Collection
    {
        return $results
            ->when($filters['menu_group'] !== '', fn (Collection $items): Collection => $items->where('menu_group', $filters['menu_group']))
            ->when($filters['menu_name'] !== '', fn (Collection $items): Collection => $items->where('menu_name', $filters['menu_name']))
            ->when($filters['resource_name'] !== '', fn (Collection $items): Collection => $items->where('resource_name', $filters['resource_name']))
            ->values();
    }

    private function paginate(Collection $results, array $filters): LengthAwarePaginator
    {
        $page = max(1, $filters['page']);
        $perPage = max(1, $filters['per_page']);

        return (new LengthAwarePaginator(
            items: $results->forPage($page, $perPage)->values(),
            total: $results->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        ))->appends(request()->query());
    }

    private function optionValues(Collection $results, string $key): array
    {
        return $results
            ->pluck($key)
            ->filter()
            ->unique()
            ->sort()
            ->map(fn (string $value): array => [
                'label' => $value,
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    private function resultRow(
        string $menuGroup,
        string $menuName,
        string $resourceName,
        string $title,
        string $url,
        array $details,
        string $icon,
    ): array {
        return [
            'menu_group' => $menuGroup,
            'menu_name' => $menuName,
            'resource_name' => $resourceName,
            'title' => $title,
            'url' => $url,
            'details' => collect($details)
                ->map(fn ($value): string => trim((string) $value))
                ->filter()
                ->take(4)
                ->all(),
            'icon' => $icon,
        ];
    }
}
