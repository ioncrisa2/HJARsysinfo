<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\JenisListing;
use App\Models\Pembanding;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response | RedirectResponse
    {
        if ((bool) $request->user()?->hasRole('super_admin')) {
            return redirect()->to(Filament::getUrl());
        }

        $mapPoints = Pembanding::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->with('jenisListing:id,name')
            ->get(['id', 'alamat_data', 'latitude', 'longitude', 'tanggal_data', 'harga', 'jenis_listing_id', 'image'])
            ->map(fn(Pembanding $row): array => [
                'id'           => $row->id,
                'alamat'       => $row->alamat_data,
                'latitude'     => (float) $row->latitude,
                'longitude'    => (float) $row->longitude,
                'tanggal'      => $row->tanggal_data,
                'harga'        => $row->harga,
                'jenis_listing_id' => $row->jenis_listing_id ? (int) $row->jenis_listing_id : null,
                'jenis_listing' => $row->jenisListing?->name,
                'detail_url'   => url("/home/pembanding/{$row->id}"),
                'image_url'    => $row->image_path,
            ])
            ->values();

        $recentData = Pembanding::query()
            ->orderByDesc('created_at')
            ->limit(8)
            ->with(['jenisListing:id,name', 'jenisObjek:id,name'])
            ->get(['id', 'alamat_data', 'harga', 'tanggal_data', 'jenis_listing_id', 'jenis_objek_id', 'image', 'created_at'])
            ->map(fn(Pembanding $row): array => [
                'id'            => $row->id,
                'alamat'        => $row->alamat_data,
                'harga'         => $row->harga,
                'tanggal'       => $row->tanggal_data,
                'created_at'    => optional($row->created_at)->toIso8601String(),
                'image_url'     => $row->image_path,
                'jenis_listing' => $row->jenisListing?->name,
                'jenis_objek'   => $row->jenisObjek?->name,
            ])
            ->values();

        $stats = [
            'total'          => Pembanding::count(),
            'this_month'     => Pembanding::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'last_month'     => Pembanding::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count(),
            'with_coords'    => Pembanding::whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'province_count' => Pembanding::whereNotNull('province_id')->distinct('province_id')->count('province_id'),
        ];

        $monthlyData = collect(range(11, 0))->map(function (int $monthsAgo): array {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->translatedFormat('M Y'),
                'count' => Pembanding::whereBetween('created_at', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth(),
                ])->count(),
            ];
        })->values();

        $startRange = now()->subMonths(11)->startOfMonth();
        $endRange = now()->endOfMonth();

        $monthBuckets = collect(range(11, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->startOfMonth())
            ->values();

        $monthKeys = $monthBuckets->map(fn ($date) => $date->format('Y-m'))->values();
        $monthLabels = $monthBuckets->map(fn ($date) => $date->translatedFormat('M Y'))->values();

        $listingCounts = Pembanding::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, jenis_listing_id, COUNT(*) as total")
            ->whereBetween('created_at', [$startRange, $endRange])
            ->whereNotNull('jenis_listing_id')
            ->groupBy('month_key', 'jenis_listing_id')
            ->get();

        $countMap = [];
        $totalByListing = [];

        foreach ($listingCounts as $row) {
            $monthKey = (string) $row->month_key;
            $listingId = (int) $row->jenis_listing_id;
            $total = (int) $row->total;

            if (!isset($countMap[$monthKey])) {
                $countMap[$monthKey] = [];
            }

            $countMap[$monthKey][$listingId] = $total;
            $totalByListing[$listingId] = ($totalByListing[$listingId] ?? 0) + $total;
        }

        $monthlyTotals = [];
        foreach ($monthKeys as $monthKey) {
            $monthlyTotals[$monthKey] = array_sum($countMap[$monthKey] ?? []);
        }

        $listingNames = JenisListing::query()
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name]);

        $topListingIds = collect($totalByListing)
            ->sortDesc()
            ->keys()
            ->take(5)
            ->map(fn ($id) => (int) $id)
            ->values();

        $listingRatioMonthly = [
            'labels' => $monthLabels->all(),
            'month_totals' => $monthKeys->map(fn ($monthKey) => (int) ($monthlyTotals[$monthKey] ?? 0))->all(),
            'series' => $topListingIds->map(function (int $listingId) use ($listingNames, $monthKeys, $countMap, $monthlyTotals): array {
                return [
                    'id' => $listingId,
                    'name' => $listingNames[$listingId] ?? "Listing {$listingId}",
                    'ratios' => $monthKeys->map(function (string $monthKey) use ($listingId, $countMap, $monthlyTotals): float {
                        $count = (int) ($countMap[$monthKey][$listingId] ?? 0);
                        $monthTotal = (int) ($monthlyTotals[$monthKey] ?? 0);

                        if ($monthTotal === 0) {
                            return 0.0;
                        }

                        return round(($count / $monthTotal) * 100, 2);
                    })->all(),
                    'counts' => $monthKeys->map(fn (string $monthKey): int => (int) ($countMap[$monthKey][$listingId] ?? 0))->all(),
                ];
            })->all(),
        ];

        $topContributors = DB::table('data_pembanding as dp')
            ->leftJoin('users as u', 'u.id', '=', 'dp.created_by')
            ->whereNull('dp.deleted_at')
            ->whereNotNull('dp.created_by')
            ->selectRaw('dp.created_by, COALESCE(u.name, "Tidak diketahui") as name, COUNT(*) as total_input')
            ->groupBy('dp.created_by', 'u.name')
            ->orderByDesc('total_input')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'total_input' => (int) $row->total_input,
            ])
            ->values();

        $today = now()->startOfDay();
        $days30 = $today->copy()->subDays(30)->toDateString();
        $days31 = $today->copy()->subDays(31)->toDateString();
        $days90 = $today->copy()->subDays(90)->toDateString();

        $fresh0to30 = Pembanding::query()
            ->whereNotNull('tanggal_data')
            ->whereDate('tanggal_data', '>=', $days30)
            ->count();

        $fresh31to90 = Pembanding::query()
            ->whereNotNull('tanggal_data')
            ->whereDate('tanggal_data', '<=', $days31)
            ->whereDate('tanggal_data', '>=', $days90)
            ->count();

        $staleOver90 = Pembanding::query()
            ->whereNotNull('tanggal_data')
            ->whereDate('tanggal_data', '<', $days90)
            ->count();

        $missingDate = Pembanding::query()->whereNull('tanggal_data')->count();
        $freshnessTotal = (int) ($stats['total'] ?? 0);

        $toPercent = fn (int $count): float => $freshnessTotal > 0 ? round(($count / $freshnessTotal) * 100, 2) : 0.0;

        $dataFreshness = [
            'basis' => 'Berdasarkan tanggal_data',
            'total' => $freshnessTotal,
            'with_date' => max($freshnessTotal - $missingDate, 0),
            'missing_date' => $missingDate,
            'buckets' => [
                [
                    'key' => 'fresh_0_30',
                    'label' => '0-30 hari',
                    'count' => $fresh0to30,
                    'percentage' => $toPercent($fresh0to30),
                    'color' => 'emerald',
                ],
                [
                    'key' => 'fresh_31_90',
                    'label' => '31-90 hari',
                    'count' => $fresh31to90,
                    'percentage' => $toPercent($fresh31to90),
                    'color' => 'amber',
                ],
                [
                    'key' => 'stale_over_90',
                    'label' => '> 90 hari',
                    'count' => $staleOver90,
                    'percentage' => $toPercent($staleOver90),
                    'color' => 'rose',
                ],
            ],
        ];

        $areaSince = now()->subDays(30)->startOfDay();
        $pembandingTable = (new Pembanding())->getTable();

        $topAreaRows = Pembanding::query()
            ->join('districts as d', 'd.id', '=', "{$pembandingTable}.district_id")
            ->whereNotNull("{$pembandingTable}.district_id")
            ->where("{$pembandingTable}.created_at", '>=', $areaSince)
            ->selectRaw("{$pembandingTable}.district_id, d.name as district_name, COUNT(*) as total_input")
            ->groupBy("{$pembandingTable}.district_id", 'd.name')
            ->orderByDesc('total_input')
            ->limit(10)
            ->get();

        $topAreaTotal = Pembanding::query()
            ->whereNotNull('district_id')
            ->where('created_at', '>=', $areaSince)
            ->count();

        $topAreaActivity = [
            'period_label' => '30 hari terakhir',
            'total_input' => (int) $topAreaTotal,
            'rows' => $topAreaRows
                ->map(fn ($row): array => [
                    'district_id' => (string) $row->district_id,
                    'district_name' => (string) $row->district_name,
                    'total_input' => (int) $row->total_input,
                    'percentage' => $topAreaTotal > 0 ? round(((int) $row->total_input / $topAreaTotal) * 100, 2) : 0.0,
                ])
                ->values(),
        ];

        $objectTypeRows = DB::table('master_jenis_objek as jo')
            ->leftJoin('data_pembanding as dp', function ($join) {
                $join->on('dp.jenis_objek_id', '=', 'jo.id')
                    ->whereNull('dp.deleted_at');
            })
            ->selectRaw('jo.id, jo.name, COUNT(dp.id) as total_input')
            ->groupBy('jo.id', 'jo.name')
            ->orderByDesc('total_input')
            ->orderBy('jo.name')
            ->get();

        $objectTypeCounts = [
            'total_records' => (int) ($stats['total'] ?? 0),
            'rows' => $objectTypeRows
                ->map(function ($row) use ($stats): array {
                    $totalRecords = (int) ($stats['total'] ?? 0);
                    $count = (int) $row->total_input;

                    return [
                        'id' => (int) $row->id,
                        'name' => (string) $row->name,
                        'total_input' => $count,
                        'percentage' => $totalRecords > 0 ? round(($count / $totalRecords) * 100, 2) : 0.0,
                    ];
                })
                ->values(),
        ];

        $jenisListingOptions = JenisListing::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($item) => ['label' => $item->name, 'value' => $item->id])
            ->values();

        return Inertia::render('Dashboard', [
            'mapPoints'          => $mapPoints,
            'recentData'         => $recentData,
            'stats'              => $stats,
            'monthlyData'        => $monthlyData,
            'listingRatioMonthly' => $listingRatioMonthly,
            'topContributors'    => $topContributors,
            'dataFreshness'      => $dataFreshness,
            'topAreaActivity'    => $topAreaActivity,
            'objectTypeCounts'   => $objectTypeCounts,
            'jenisListingOptions' => $jenisListingOptions,
        ]);
    }
}
