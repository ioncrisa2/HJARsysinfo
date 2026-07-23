<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Supports\DictionaryTypeMap;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

#[Group('Dictionary', 'Referensi master data properti yang dipakai oleh client API.', weight: 2)]
class DictionaryController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat dictionary',
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
    #[Response(
        status: 200,
        description: 'Dictionary berhasil diambil.',
        type: "array{status: 'success', message: string, data: list<array{id: int, name: string, slug: string, sort_order: int, is_active: bool, badge_color_token: string|null, marker_icon_url: string|null}>}"
    )]
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
