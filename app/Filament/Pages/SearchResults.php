<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Throwable;

class SearchResults extends Page
{
    use WithPagination;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'search-results';
    protected static ?string $title = 'Search Results';
    protected static string $view = 'filament.pages.search-results';

    public string $query = '';
    public string $menuGroup = '';
    public string $menuName = '';
    public string $resourceName = '';
    public int $perPage = 15;

    protected int $perResourceLimit = 40;

    public function mount(): void
    {
        $this->query = trim((string) request()->query('q', ''));
    }

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedMenuGroup(): void
    {
        $this->menuName = '';
        $this->resourceName = '';

        $this->resetPage();
    }

    public function updatedMenuName(): void
    {
        $this->resourceName = '';

        $this->resetPage();
    }

    public function updatedResourceName(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->menuGroup = '';
        $this->menuName = '';
        $this->resourceName = '';
        $this->perPage = 15;

        $this->resetPage();
    }

    public function getRawResultsProperty(): Collection
    {
        $keyword = trim($this->query);

        if ($keyword === '') {
            return collect();
        }

        $results = collect();

        foreach (Filament::getResources() as $resourceClass) {
            if (! is_subclass_of($resourceClass, Resource::class)) {
                continue;
            }

            try {
                if (! $resourceClass::canAccess()) {
                    continue;
                }

                $results = $results->merge($this->searchResource($resourceClass, $keyword));
            } catch (Throwable) {
                // Skip resource that cannot be searched safely.
            }
        }

        return $results
            ->unique(fn (array $row): string => $row['url'] . '|' . $row['resource_class'])
            ->sortBy(fn (array $row): string => Str::lower(
                "{$row['menu_group']}|{$row['menu_name']}|{$row['resource_name']}|{$row['title']}"
            ))
            ->values();
    }

    public function getFilteredResultsProperty(): Collection
    {
        return $this->rawResults
            ->when(
                $this->menuGroup !== '',
                fn (Collection $results): Collection => $results->where('menu_group', $this->menuGroup)
            )
            ->when(
                $this->menuName !== '',
                fn (Collection $results): Collection => $results->where('menu_name', $this->menuName)
            )
            ->when(
                $this->resourceName !== '',
                fn (Collection $results): Collection => $results->where('resource_name', $this->resourceName)
            )
            ->values();
    }

    public function getPaginatedResultsProperty(): LengthAwarePaginator
    {
        $perPage = max(1, (int) $this->perPage);
        $currentPage = $this->getPage();
        $results = $this->filteredResults;

        $pageItems = $results
            ->forPage($currentPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            items: $pageItems,
            total: $results->count(),
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );
    }

    public function getMenuGroupOptionsProperty(): array
    {
        return $this->rawResults
            ->pluck('menu_group')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getMenuNameOptionsProperty(): array
    {
        return $this->rawResults
            ->when(
                $this->menuGroup !== '',
                fn (Collection $results): Collection => $results->where('menu_group', $this->menuGroup)
            )
            ->pluck('menu_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getResourceNameOptionsProperty(): array
    {
        return $this->rawResults
            ->when(
                $this->menuGroup !== '',
                fn (Collection $results): Collection => $results->where('menu_group', $this->menuGroup)
            )
            ->when(
                $this->menuName !== '',
                fn (Collection $results): Collection => $results->where('menu_name', $this->menuName)
            )
            ->pluck('resource_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function searchResource(string $resourceClass, string $keyword): Collection
    {
        if ($resourceClass::canGloballySearch()) {
            return $this->mapGlobalSearchResults(
                resourceClass: $resourceClass,
                results: $resourceClass::getGlobalSearchResults($keyword),
            );
        }

        return $this->runFallbackResourceSearch($resourceClass, $keyword);
    }

    private function mapGlobalSearchResults(string $resourceClass, Collection $results): Collection
    {
        $context = $this->resolveMenuContext($resourceClass);
        $resourceName = $this->resolveResourceName($resourceClass);

        return $results
            ->map(function (GlobalSearchResult $result) use ($context, $resourceClass, $resourceName): array {
                return [
                    'resource_class' => $resourceClass,
                    'resource_name' => $resourceName,
                    'menu_group' => $context['group'],
                    'menu_name' => $context['menu'],
                    'title' => $this->stringifyValue($result->title),
                    'url' => $result->url,
                    'details' => $this->normalizeDetails($result->details),
                ];
            })
            ->filter(fn (array $row): bool => $row['url'] !== '')
            ->values();
    }

    private function runFallbackResourceSearch(string $resourceClass, string $keyword): Collection
    {
        $attributes = $this->resolveFallbackSearchAttributes($resourceClass);

        if ($attributes === []) {
            return collect();
        }

        $query = $resourceClass::getGlobalSearchEloquentQuery();

        $this->applyKeywordConstraints($query, $keyword, $attributes);

        $records = $query
            ->limit($this->perResourceLimit)
            ->get();

        $context = $this->resolveMenuContext($resourceClass);
        $resourceName = $this->resolveResourceName($resourceClass);

        return $records
            ->map(function (Model $record) use ($attributes, $context, $resourceClass, $resourceName): ?array {
                $url = (string) ($resourceClass::getGlobalSearchResultUrl($record) ?? '');

                if ($url === '') {
                    return null;
                }

                $title = $this->resolveFallbackTitle($resourceClass, $record, $attributes);
                $details = $resourceClass::getGlobalSearchResultDetails($record);

                if ($details === []) {
                    $details = $this->resolveFallbackDetails($record, $attributes, $title);
                }

                return [
                    'resource_class' => $resourceClass,
                    'resource_name' => $resourceName,
                    'menu_group' => $context['group'],
                    'menu_name' => $context['menu'],
                    'title' => $title,
                    'url' => $url,
                    'details' => $this->normalizeDetails($details),
                ];
            })
            ->filter()
            ->values();
    }

    private function resolveMenuContext(string $resourceClass): array
    {
        $clusterClass = $resourceClass::getCluster();

        if ($clusterClass !== null) {
            return [
                'group' => (string) ($clusterClass::getNavigationGroup() ?? $resourceClass::getNavigationGroup() ?? 'Lainnya'),
                'menu' => (string) ($clusterClass::getNavigationLabel() ?? 'Lainnya'),
            ];
        }

        return [
            'group' => (string) ($resourceClass::getNavigationGroup() ?? 'Lainnya'),
            'menu' => (string) ($resourceClass::getNavigationLabel() ?: $resourceClass::getPluralModelLabel()),
        ];
    }

    private function resolveResourceName(string $resourceClass): string
    {
        return (string) ($resourceClass::getNavigationLabel() ?: $resourceClass::getModelLabel());
    }

    private function resolveFallbackSearchAttributes(string $resourceClass): array
    {
        $modelClass = $resourceClass::getModel();
        $model = new $modelClass();
        $columns = Schema::getColumnListing($model->getTable());

        $candidates = [
            'name',
            'title',
            'alamat_data',
            'nama_pemberi_informasi',
            'nomer_telepon_pemberi_informasi',
            'email',
            'slug',
            'id',
        ];

        return array_values(array_intersect($candidates, $columns));
    }

    private function applyKeywordConstraints(Builder $query, string $keyword, array $attributes): void
    {
        $words = collect(preg_split('/\s+/', trim($keyword)) ?: [])
            ->filter(fn (string $word): bool => $word !== '')
            ->values();

        foreach ($words as $word) {
            $query->where(function (Builder $nestedQuery) use ($attributes, $word): void {
                $isFirst = true;

                foreach ($attributes as $attribute) {
                    $method = $isFirst ? 'where' : 'orWhere';

                    if (str_contains($attribute, '.')) {
                        $relation = Str::beforeLast($attribute, '.');
                        $column = Str::afterLast($attribute, '.');

                        $nestedQuery->{"{$method}Has"}($relation, function (Builder $relationQuery) use ($column, $word): void {
                            $relationQuery->where($column, 'like', "%{$word}%");
                        });
                    } else {
                        $nestedQuery->{$method}($attribute, 'like', "%{$word}%");
                    }

                    $isFirst = false;
                }
            });
        }
    }

    private function resolveFallbackTitle(string $resourceClass, Model $record, array $attributes): string
    {
        $globalTitle = $this->stringifyValue($resourceClass::getGlobalSearchResultTitle($record));

        if ($globalTitle !== '' && $globalTitle !== $resourceClass::getModelLabel()) {
            return $globalTitle;
        }

        $priorityColumns = [
            'name',
            'title',
            'alamat_data',
            'nama_pemberi_informasi',
            'email',
            'id',
        ];

        foreach ($priorityColumns as $column) {
            if (! in_array($column, $attributes, true)) {
                continue;
            }

            $value = $this->stringifyValue($record->getAttribute($column));

            if ($value !== '') {
                return $value;
            }
        }

        return $resourceClass::getModelLabel() . ' #' . $record->getKey();
    }

    private function resolveFallbackDetails(Model $record, array $attributes, string $title): array
    {
        $details = [];

        foreach ($attributes as $attribute) {
            if (str_contains($attribute, '.')) {
                continue;
            }

            $value = $this->stringifyValue($record->getAttribute($attribute));

            if ($value === '' || $value === $title) {
                continue;
            }

            $details[(string) Str::of($attribute)->replace('_', ' ')->title()] = $value;

            if (count($details) >= 3) {
                break;
            }
        }

        return $details;
    }

    private function normalizeDetails(array $details): array
    {
        return collect($details)
            ->mapWithKeys(function ($value, $label): array {
                return [(string) $label => $this->stringifyValue($value)];
            })
            ->filter(fn (string $value): bool => $value !== '')
            ->take(4)
            ->all();
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        return trim(strip_tags((string) $value));
    }
}
