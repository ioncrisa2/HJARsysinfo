<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisListing;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\User;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Stats Overview
        $stats = [
            'total_users' => User::count(),
            'total_pembanding' => Pembanding::count(),
            'pending_deletions' => PembandingDeleteRequest::where('status', 'pending')->count(),
        ];

        // 2. Data Entry Trend Chart (Monthly this year)
        $trendData = Trend::model(Pembanding::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        $trendChart = [
            'labels' => $trendData->map(fn (TrendValue $value) => $value->date),
            'datasets' => [
                [
                    'label' => 'Jumlah Data Masuk',
                    'data' => $trendData->map(fn (TrendValue $value) => $value->aggregate),
                ]
            ],
        ];

        // 3. Listing Composition Chart
        $countsById = Pembanding::query()
            ->selectRaw('jenis_listing_id, COUNT(*) as total')
            ->whereNotNull('jenis_listing_id')
            ->groupBy('jenis_listing_id')
            ->pluck('total', 'jenis_listing_id');

        $listings = JenisListing::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'badge_color']);

        $compositionChart = [
            'labels' => $listings->pluck('name'),
            'datasets' => [
                [
                    'data' => $listings->map(fn ($l) => (int) ($countsById[$l->id] ?? 0)),
                    'backgroundColor' => $listings->map(fn ($l) => $l->badge_color ?: '#64748b'),
                ]
            ],
        ];

        // 4. Latest Pembanding Table
        $latestPembanding = Pembanding::query()
            ->with(['jenisListing:id,name,badge_color'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'alamat_data' => $item->alamat_data,
                    'harga' => $item->harga,
                    'jenis_listing' => [
                        'name' => $item->jenisListing?->name,
                        'badge_color' => $item->jenisListing?->badge_color,
                    ],
                    'created_at' => $item->created_at->diffForHumans(),
                ];
            });

        // 5. Map Markers
        $markers = Pembanding::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['jenisListing:id,slug,name,badge_color,marker_icon_url'])
            ->limit(500) // Optimization: limit markers for dashboard performance
            ->get(['id', 'alamat_data', 'latitude', 'longitude', 'jenis_listing_id', 'image'])
            ->map(function ($item) {
                // Image URL
                $imgUrl = 'https://placehold.co/600x400?text=No+Image';
                if ($item->image) {
                    $path = ltrim($item->image, './');
                    if (!str_starts_with($path, 'foto_pembanding/')) {
                        $path = 'foto_pembanding/' . $path;
                    }
                    $imgUrl = Storage::disk('public')->url($path);
                }

                return [
                    'id' => $item->id,
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'alamat' => $item->alamat_data,
                    'img_url' => $imgUrl,
                    'listing_name' => $item->jenisListing?->name ?? '-',
                    'badge_color' => $item->jenisListing?->badge_color ?: '#64748b',
                    'icon' => $item->jenisListing?->marker_icon_url ?: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'trendChart' => $trendChart,
            'compositionChart' => $compositionChart,
            'latestPembanding' => $latestPembanding,
            'markers' => $markers,
        ]);
    }
}
