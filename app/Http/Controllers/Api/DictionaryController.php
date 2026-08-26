<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Supports\DictionaryTypeMap;
use App\Supports\Slug;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

#[Group('Dictionary', 'Master data kategori dan referensi properti aplikasi.', weight: 2)]
class DictionaryController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat daftar kategori dictionary',
        description: 'Mengembalikan daftar seluruh tipe dictionary beserta metadata label, icon, dan jumlah data aktif/nonaktif.'
    )]
    public function definitions(): JsonResponse
    {
        $categories = collect(DictionaryTypeMap::definitions())
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
            ->values();

        return $this->success($categories, 'Daftar kategori dictionary berhasil diambil.');
    }

    #[Endpoint(
        title: 'Lihat isi dictionary berdasarkan tipe',
        description: 'Mengembalikan nilai master data berdasarkan tipe. Data nonaktif hanya tersedia bagi pengguna dengan permission view_master_data.'
    )]
    #[PathParameter(
        'type',
        description: 'Tipe dictionary.',
        type: "'jenis-listing'|'jenis-objek'|'status-pemberi-informasi'|'bentuk-tanah'|'dokumen-tanah'|'posisi-tanah'|'kondisi-tanah'|'topografi'|'peruntukan'",
        example: 'jenis-objek'
    )]
    #[QueryParameter(
        'active_only',
        description: 'Jika false, sertakan data nonaktif (memerlukan permission view_master_data).',
        type: 'bool',
        default: true,
        example: true
    )]
    public function index(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);
        $activeOnly = $request->boolean('active_only', true);

        if (! $activeOnly && ! $request->user()?->can('view_master_data')) {
            return $this->error('Tidak diizinkan melihat dictionary nonaktif.', 403, null, 'FORBIDDEN');
        }

        $cacheKey = 'api_dictionary_'.$type.'_'.($activeOnly ? 'active' : 'all');

        $items = Cache::remember($cacheKey, now()->addHours(24), function () use ($model, $activeOnly) {
            $query = $model::query()
                ->withCount(['pembandings' => fn ($q) => $q->withTrashed()])
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
                    'badge_color',
                    'marker_icon_url',
                    'pembandings_count',
                ]))
                ->values();
        });

        return $this->success($items, "Data dictionary {$type}");
    }

    #[Endpoint(
        title: 'Tambah item dictionary',
        description: 'Membuat rekaman baru untuk tipe dictionary yang ditentukan.'
    )]
    public function store(Request $request, string $type): JsonResponse
    {
        $model = $this->resolveModel($type);

        $data = $this->validateData($request, $model, null, $type);
        $data['sort_order'] = $this->nextSortOrder($model);
        $record = $model::create($data);

        $this->clearDictionaryCache($type);

        return $this->success($this->loadUsageCount($record), 'Data dictionary berhasil ditambahkan.', 201);
    }

    #[Endpoint(
        title: 'Ubah item dictionary',
        description: 'Memperbarui nama dan atribut item dictionary.'
    )]
    public function update(Request $request, string $type, int|string $id): JsonResponse
    {
        $model = $this->resolveModel($type);
        $record = $model::findOrFail($id);

        if ($request->has('is_active') && ! $request->user()?->can('update_master_data_status')) {
            return $this->error('Tidak diizinkan mengubah status data master.', 403, null, 'FORBIDDEN');
        }

        $data = $this->validateData($request, $model, (int) $record->id, $type);
        $record->update($data);

        $this->clearDictionaryCache($type);

        return $this->success($this->loadUsageCount($record), 'Data dictionary berhasil diperbarui.');
    }

    #[Endpoint(
        title: 'Ubah status aktif/nonaktif item dictionary',
        description: 'Mengaktifkan atau menonaktifkan item dictionary.'
    )]
    public function updateStatus(Request $request, string $type, int|string $id): JsonResponse
    {
        $model = $this->resolveModel($type);
        $record = $model::findOrFail($id);
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $record->update(['is_active' => $data['is_active']]);

        $this->clearDictionaryCache($type);

        return $this->success($this->loadUsageCount($record), 'Status dictionary berhasil diubah.');
    }

    #[Endpoint(
        title: 'Hapus item dictionary',
        description: 'Menghapus rekaman dictionary jika tidak sedang digunakan oleh data pembanding.'
    )]
    public function destroy(string $type, int|string $id): JsonResponse
    {
        $model = $this->resolveModel($type);
        $record = $model::findOrFail($id);

        if ($record->pembandings()->withTrashed()->exists()) {
            return $this->error(
                "{$record->name} masih digunakan oleh Data Pembanding. Nonaktifkan data ini agar riwayat tetap utuh.",
                422,
                ['delete' => ["{$record->name} masih digunakan oleh Data Pembanding. Nonaktifkan data ini agar riwayat tetap utuh."]],
                'VALIDATION_FAILED'
            );
        }

        $record->delete();

        $this->clearDictionaryCache($type);

        return $this->success(null, 'Data dictionary berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Urutkan ulang (reorder) item dictionary',
        description: 'Memperbarui susunan sort_order untuk seluruh item dictionary dalam suatu tipe.'
    )]
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
            return $this->error(
                'Daftar urutan harus mencakup semua data.',
                422,
                ['ids' => ['Daftar urutan harus mencakup semua data.']],
                'VALIDATION_FAILED'
            );
        }

        $existing = $model::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $sortedIds = $ids;
        $sortedExisting = $existing;
        sort($sortedIds);
        sort($sortedExisting);

        if ($sortedIds !== $sortedExisting) {
            return $this->error(
                'Daftar urutan tidak valid. Muat ulang data lalu coba lagi.',
                422,
                ['ids' => ['Daftar urutan tidak valid. Muat ulang data lalu coba lagi.']],
                'VALIDATION_FAILED'
            );
        }

        DB::transaction(function () use ($model, $data): void {
            foreach ($data['ids'] as $index => $id) {
                $model::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        $this->clearDictionaryCache($type);

        return $this->success(null, 'Urutan dictionary berhasil diperbarui.');
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
                'badge_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'marker_icon_url' => ['nullable', 'url', 'max:1000'],
            ];
        }

        $data = $request->validate($base + $extra);

        $data['slug'] = Slug::snake($data['name'] ?? '');
        $data['is_active'] = $data['is_active'] ?? true;

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

    private function loadUsageCount($record)
    {
        return $record->loadCount(['pembandings' => fn ($query) => $query->withTrashed()]);
    }

    private function clearDictionaryCache(string $type): void
    {
        Cache::forget('api_dictionary_'.$type.'_active');
        Cache::forget('api_dictionary_'.$type.'_all');
        Cache::forget('pembanding_form_options');
    }
}
