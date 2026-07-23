<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembanding;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

#[Group('Data Pembanding', 'Pencarian dan pengelolaan data pembanding properti.', weight: 4)]
class PembandingMapController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat sebaran pembanding',
        description: 'Mengembalikan koordinat dan informasi ringkas seluruh pembanding yang memiliki lokasi.'
    )]
    #[Response(
        status: 200,
        description: 'Marker peta berhasil diambil.',
        type: "array{status: 'success', message: string, data: list<array{latitude: float, longitude: float, alamat_data: string, image_url: string|null}>}"
    )]
    public function __invoke(): JsonResponse
    {
        Gate::authorize('viewMap', Pembanding::class);

        $markers = Pembanding::query()
            ->withoutEagerLoads()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['latitude', 'longitude', 'alamat_data', 'image'])
            ->map(fn (Pembanding $pembanding): array => [
                'latitude' => (float) $pembanding->latitude,
                'longitude' => (float) $pembanding->longitude,
                'alamat_data' => $pembanding->alamat_data,
                'image_url' => filled($pembanding->image)
                    ? Storage::disk('public')->url($pembanding->image)
                    : null,
            ]);

        return $this->success($markers, 'Data sebaran pembanding');
    }
}
