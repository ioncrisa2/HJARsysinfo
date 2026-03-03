<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Supports\DictionaryTypeMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DictionaryController extends Controller
{
    public function index(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);

        $query = $model::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $items = $query
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

        return response()->json($items);
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
