<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Supports\DictionaryTypeMap;
use App\Supports\Slug;

class DictionaryApiController extends Controller
{
    public function index(string $type): JsonResponse
    {
        $model = $this->resolveModel($type);

        $items = $model::query()
            ->orderBy('sort_order')
            ->orderBy('name')
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

    public function store(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);

        $data = $this->validateData($request, $model, null, $type);
        $data['sort_order'] = $this->nextSortOrder($model);
        $record = $model::create($data);

        return response()->json($record, 201);
    }

    public function update(Request $request, string $type, int|string $id): JsonResponse
    {
        $model = $this->resolveModel($type);
        $record = $model::findOrFail($id);

        $data = $this->validateData($request, $model, $record->id, $type);
        $record->update($data);

        return response()->json($record);
    }

    public function destroy(string $type, int|string $id): JsonResponse
    {
        $model = $this->resolveModel($type);
        $record = $model::findOrFail($id);
        $record->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $ids = array_map('intval', $data['ids']);

        $total = $model::query()->count();
        if (count($ids) !== $total) {
            throw ValidationException::withMessages([
                'ids' => ['Daftar urutan harus mencakup semua data.'],
            ]);
        }

        $existing = $model::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($ids);
        sort($existing);

        if ($ids !== $existing) {
            throw ValidationException::withMessages([
                'ids' => ['Daftar urutan tidak valid. Muat ulang data lalu coba lagi.'],
            ]);
        }

        DB::transaction(function () use ($model, $data): void {
            foreach ($data['ids'] as $index => $id) {
                $model::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    private function resolveModel(string $type): string
    {
        $model = DictionaryTypeMap::resolveModel($type);

        if (! $model) {
            abort(404, 'Dictionary type not found.');
        }

        return $model;
    }

    private function validateData(Request $request, string $model, ?int $ignoreId, string $type): array
    {
        $base = [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        $extra = [];

        if ($type === 'jenis-listing') {
            $extra = [
                'badge_color_token' => ['nullable', Rule::in(['gray', 'primary', 'info', 'success', 'warning', 'danger'])],
                'marker_icon_url' => ['nullable', 'url', 'max:1000'],
            ];
        }

        $data = $request->validate($base + $extra);

        $data['slug'] = Slug::snake($data['name'] ?? '');
        $data['is_active'] = $data['is_active'] ?? true;

        // Ensure generated slug is valid and unique
        validator(
            ['slug' => $data['slug']],
            [
                'slug' => [
                    'required',
                    'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/',
                    Rule::unique((new $model)->getTable(), 'slug')->ignore($ignoreId),
                ],
            ],
        )->validate();

        return $data;
    }

    private function nextSortOrder(string $model): int
    {
        $max = $model::query()->max('sort_order');

        return $max === null ? 1 : ((int) $max + 1);
    }
}
