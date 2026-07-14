<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\AppAccess;
use App\Supports\DictionaryTypeMap;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataPageController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Index', [
            'categories' => $this->categoriesWithStats(),
        ]);
    }

    public function show(Request $request, string $type): Response
    {
        $definition = DictionaryTypeMap::resolve($type);
        abort_unless($definition, 404);

        $model = $definition['model'];
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), ['active', 'inactive'], true)
                ? $request->query('status')
                : 'all',
        ];

        return Inertia::render('MasterData/Show', [
            'category' => collect($definition)->except('model')->all(),
            'items' => $model::query()
                ->withCount(['pembandings' => fn ($query) => $query->withTrashed()])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($record): array => $record->only([
                    'id',
                    'name',
                    'sort_order',
                    'is_active',
                    'badge_color',
                    'marker_icon_url',
                    'pembandings_count',
                ]))
                ->values()
                ->all(),
            'filters' => $filters,
            'can' => AppAccess::capabilityMap($request->user(), [
                'create' => 'create_master_data',
                'update' => 'update_master_data',
                'update_status' => 'update_master_data_status',
                'delete' => ['delete_master_data', 'delete_any_master_data'],
                'reorder' => 'reorder_master_data',
            ]),
            'breadcrumbs' => [
                ['label' => 'Beranda', 'href' => '/app', 'icon' => 'pi-home'],
                ['label' => 'Master Data', 'href' => '/app/master-data'],
                ['label' => $definition['label'], 'href' => null],
            ],
        ]);
    }

    private function categoriesWithStats(): array
    {
        return collect(DictionaryTypeMap::definitions())
            ->map(function (array $definition): array {
                $model = $definition['model'];
                $total = $model::query()->count();
                $active = $model::query()->where('is_active', true)->count();

                return [
                    ...collect($definition)->except('model')->all(),
                    'stats' => [
                        'total' => $total,
                        'active' => $active,
                        'inactive' => $total - $active,
                    ],
                ];
            })
            ->all();
    }
}
