<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
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
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Group('Global Search', 'Pencarian terpadu lintas modul aplikasi (pembanding, user, referensi master data, geo lokasi, moderasi).', weight: 12)]
class SearchController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    private const PER_RESOURCE_LIMIT = 40;

    private array $geoResources = [
        'provinces' => [Province::class, 'Provinsi'],
        'regencies' => [Regency::class, 'Kabupaten / Kota'],
        'districts' => [District::class, 'Kecamatan'],
        'villages' => [Village::class, 'Desa / Kelurahan'],
    ];

    #[Endpoint(
        title: 'Pencarian global lintas modul',
        description: 'Mencari entitas di seluruh sistem berdasarkan kata kunci dengan filter grup menu dan paginasi.'
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizePermission('view_search');

        $filters = $this->filters($request);
        $rawResults = $filters['q'] !== '' ? $this->search($request, $filters['q']) : collect();
        $filteredResults = $this->applyResultFilters($rawResults, $filters);
        $paginated = $this->paginate($filteredResults, $filters);

        return response()->json([
            'status' => 'success',
            'message' => 'Hasil pencarian berhasil diambil.',
            'query' => $filters['q'],
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'summary' => [
                'raw_total' => $rawResults->count(),
                'filtered_total' => $filteredResults->count(),
            ],
            'options' => [
                'menu_groups' => $this->optionValues($rawResults, 'menu_group'),
                'menu_names' => $this->optionValues(
                    $filters['menu_group'] !== ''
                        ? $rawResults->where('menu_group', $filters['menu_group'])
                        : $rawResults,
                    'menu_name'
                ),
                'resource_names' => $this->optionValues(
                    $rawResults
                        ->when($filters['menu_group'] !== '', fn (Collection $results) => $results->where('menu_group', $filters['menu_group']))
                        ->when($filters['menu_name'] !== '', fn (Collection $results) => $results->where('menu_name', $filters['menu_name'])),
                    'resource_name'
                ),
            ],
        ]);
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
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

    private function search(Request $request, string $keyword): Collection
    {
        return collect()
            ->merge($this->searchUsers($request, $keyword))
            ->merge($this->searchPembanding($request, $keyword))
            ->merge($this->searchModeration($request, $keyword))
            ->merge($this->searchMasterData($request, $keyword))
            ->merge($this->searchGeoData($request, $keyword))
            ->sortBy(fn (array $row): string => Str::lower(
                "{$row['menu_group']}|{$row['menu_name']}|{$row['resource_name']}|{$row['title']}"
            ))
            ->values();
    }

    private function searchUsers(Request $request, string $keyword): Collection
    {
        if (! AppAccess::can($request->user(), 'view_any_user')) {
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
                targetType: 'user',
                targetId: (string) $user->id,
                apiUrl: "/api/v1/users/{$user->id}",
                details: [
                    'Email' => $user->email,
                    'Roles' => $user->roles->pluck('name')->implode(', '),
                    'Status' => $user->deactivated_at ? 'Inactive' : 'Active',
                ],
                icon: 'pi pi-user'
            ));
    }

    private function searchPembanding(Request $request, string $keyword): Collection
    {
        if (! AppAccess::can($request->user(), 'view_any_data::pembanding')) {
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
                targetType: 'pembanding',
                targetId: (string) $record->id,
                apiUrl: "/api/v1/pembandings/{$record->id}",
                details: [
                    'ID' => "#{$record->id}",
                    'Pemberi Info' => $record->nama_pemberi_informasi,
                    'Tipe' => collect([$record->jenisListing?->name, $record->jenisObjek?->name])->filter()->implode(' / '),
                    'Lokasi' => collect([$record->regency?->name, $record->province?->name])->filter()->implode(', '),
                ],
                icon: 'pi pi-database'
            ));
    }

    private function searchModeration(Request $request, string $keyword): Collection
    {
        if (! AppAccess::can($request->user(), 'view_moderation')) {
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
            ->map(fn (PembandingDeleteRequest $delReq): array => $this->resultRow(
                menuGroup: 'Manajemen',
                menuName: 'Moderation Desk',
                resourceName: 'Delete Request',
                title: $delReq->pembanding?->alamat_data ?: "Delete Request #{$delReq->id}",
                targetType: 'moderation_request',
                targetId: (string) $delReq->id,
                apiUrl: "/api/v1/moderation?tab=requests&search={$delReq->id}",
                details: [
                    'Status' => $delReq->status,
                    'Requested By' => $delReq->requestedBy?->name,
                    'Reason' => Str::limit((string) $delReq->reason, 120),
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
                targetType: 'moderation_trash',
                targetId: (string) $record->id,
                apiUrl: "/api/v1/moderation?tab=trash&search={$record->id}",
                details: [
                    'Deleted By' => $record->deletedBy?->name,
                    'Reason' => Str::limit((string) $record->deleted_reason, 120),
                    'Deleted At' => optional($record->deleted_at)->format('Y-m-d H:i'),
                ],
                icon: 'pi pi-trash'
            ));

        return $requests->merge($trashed);
    }

    private function searchMasterData(Request $request, string $keyword): Collection
    {
        if (! AppAccess::can($request->user(), 'view_master_data')) {
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
                        targetType: 'dictionary',
                        targetId: (string) $record->id,
                        apiUrl: "/api/v1/dictionaries/{$slug}",
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

    private function searchGeoData(Request $request, string $keyword): Collection
    {
        if (! AppAccess::can($request->user(), 'view_geo_data')) {
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
                        targetType: 'geo',
                        targetId: (string) $record->id,
                        apiUrl: "/api/v1/geo/{$slug}",
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
        string $targetType,
        string $targetId,
        string $apiUrl,
        array $details,
        string $icon,
    ): array {
        return [
            'menu_group' => $menuGroup,
            'menu_name' => $menuName,
            'resource_name' => $resourceName,
            'title' => $title,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'api_url' => $apiUrl,
            'details' => collect($details)
                ->map(fn ($value): string => trim((string) $value))
                ->filter()
                ->take(4)
                ->all(),
            'icon' => $icon,
        ];
    }
}
