<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Supports\DictionaryTypeMap;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DictionaryController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);
        $activeOnly = $request->boolean('active_only', true);

        if (! $activeOnly && ! $request->user()?->can('view_master_data')) {
            return $this->error('Tidak diizinkan melihat dictionary nonaktif.', 403);
        }

        $cacheKey = 'api_dictionary_'.$type.'_'.($activeOnly ? 'active' : 'all');

        $items = Cache::remember($cacheKey, now()->addHours(24), function () use ($model, $activeOnly) {
            $query = $model::query()
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query
                ->get()
                ->map(fn ($row) => $row->only([
                    'id',
                    'name',
                    'slug',
                    'sort_order',
                    'is_active',
                    'badge_color_token',
                    'marker_icon_url',
                ]))
                ->values();
        });

        return $this->success($items, "Data dictionary {$type}");
    }

    private function resolveModel(string $type): string
    {
        $model = DictionaryTypeMap::resolveModel($type);

        if (! $model) {
            abort(404, 'Dictionary type not found.');
        }

        return $model;
    }
}
