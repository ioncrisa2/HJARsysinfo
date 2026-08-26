<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingExportRequest;
use App\Jobs\GeneratePembandingExport;
use App\Models\ExportRun;
use App\Models\Pembanding;
use App\Models\User;
use App\Services\Exports\PembandingExportFileService;
use App\Services\Exports\PembandingExportQueryService;
use App\Services\Pembanding\PembandingBrowseFilterService;
use App\Support\AppAccess;
use App\Support\Exports\PembandingExportColumnRegistry;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Ekspor Data', 'Konfigurasi ekspor, pembuatan file ekspor sinkron/asinkron, dan riwayat file.', weight: 11)]
class ExportController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    private const LIMITS = [
        'excel' => 5000,
        'csv' => 5000,
        'geojson' => 5000,
        'kml' => 5000,
        'pdf_summary' => 1000,
        'pdf_detail' => 100,
    ];

    private const ASYNC_LIMITS = [
        'excel' => 100000,
        'csv' => 100000,
        'geojson' => 50000,
        'kml' => 50000,
        'pdf_summary' => 5000,
        'pdf_detail' => 500,
    ];

    public function __construct(
        private readonly PembandingExportQueryService $queryService,
        private readonly PembandingExportFileService $fileService,
        private readonly PembandingExportColumnRegistry $columnRegistry,
    ) {}

    #[Endpoint(
        title: 'Lihat konfigurasi ekspor',
        description: 'Mengembalikan daftar profil kolom ekspor, format yang tersedia, batasan limit baris, dan izin pengguna.'
    )]
    public function configuration(PembandingExportRequest $request): JsonResponse
    {
        $this->authorizePermission('view_export');

        $user = $request->user();
        $config = $this->columnRegistry->publicConfiguration($user);

        $can = AppAccess::capabilityMap($user, [
            'download' => 'export_data::pembanding',
            'sensitive' => 'export_sensitive_data::pembanding',
        ]);

        return $this->success([
            'configuration' => $config,
            'limits' => self::LIMITS,
            'async_limits' => self::ASYNC_LIMITS,
            'can' => $can,
        ], 'Konfigurasi ekspor berhasil diambil.');
    }

    #[Endpoint(
        title: 'Preview jumlah data yang akan diekspor',
        description: 'Menghitung estimasi total baris, batas proses langsung (sinkron), dan apakah perlu dialihkan ke antrean asinkron.'
    )]
    public function preview(PembandingExportRequest $request): JsonResponse
    {
        $this->authorizePermission('export_data::pembanding');

        $format = (string) ($request->validated('format') ?? 'excel');
        $mode = (string) ($request->validated('mode') ?? 'summary');
        $scope = (string) ($request->validated('scope') ?? 'filtered');
        $ids = $scope === 'selected' ? $this->queryService->parseIds($request->input('ids')) : [];
        $query = $this->queryService->query($request->user(), $this->requestFilters($request), $ids);
        $count = (clone $query)->count();
        $limit = $this->limitFor($format, $mode);

        return $this->success([
            'count' => $count,
            'sync_limit' => $limit,
            'queued' => $count > $limit,
            'without_coordinates' => (clone $query)->where(function ($builder): void {
                $builder->whereNull('latitude')->orWhereNull('longitude');
            })->count(),
        ], 'Preview ekspor berhasil dihitung.');
    }

    #[Endpoint(
        title: 'Lihat riwayat tugas ekspor asinkron (export runs)',
        description: 'Mengembalikan daftar tugas pembuatan file ekspor pengguna yang sedang berjalan, selesai, atau gagal.'
    )]
    public function runs(PembandingExportRequest $request): JsonResponse
    {
        $this->authorizePermission('view_export');

        $runs = $request->user()->exportRuns()
            ->latest()
            ->paginate((int) $request->integer('per_page', 10));

        $data = collect($runs->items())->map(fn (ExportRun $run): array => $this->mapExportRun($run))->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar riwayat tugas ekspor berhasil diambil.',
            'data' => $data,
            'meta' => [
                'current_page' => $runs->currentPage(),
                'per_page' => $runs->perPage(),
                'from' => $runs->firstItem(),
                'to' => $runs->lastItem(),
                'total' => $runs->total(),
                'last_page' => $runs->lastPage(),
            ],
            'links' => [
                'first' => $runs->url(1),
                'last' => $runs->url($runs->lastPage()),
                'prev' => $runs->previousPageUrl(),
                'next' => $runs->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Minta pembuatan ekspor asinkron (background queue)',
        description: 'Mendaftarkan tugas ekspor ke antrean worker background untuk jumlah data besar.'
    )]
    public function storeRun(PembandingExportRequest $request): JsonResponse
    {
        $this->authorizePermission('export_data::pembanding');

        $format = (string) ($request->validated('format') ?? 'excel');
        $mode = (string) ($request->validated('mode') ?? 'summary');
        $profile = (string) ($request->validated('profile') ?? PembandingExportColumnRegistry::DEFAULT_PROFILE);
        $scope = (string) ($request->validated('scope') ?? 'filtered');
        $filters = $this->requestFilters($request);
        $ids = $scope === 'selected' ? $this->queryService->parseIds($request->input('ids')) : [];

        if ($scope === 'selected' && $ids === []) {
            return $this->error('Pilih sedikitnya satu data untuk diexport.', 422, ['ids' => ['Pilih sedikitnya satu data untuk diexport.']], 'VALIDATION_FAILED');
        }

        $columns = $this->columnRegistry->resolveColumns($request->user(), $profile, $request->validated('columns') ?? []);
        if ($columns === []) {
            return $this->error('Tidak ada kolom yang diizinkan untuk diexport.', 422, ['columns' => ['Tidak ada kolom yang diizinkan untuk diexport.']], 'VALIDATION_FAILED');
        }

        $snapshotAt = now();
        $total = $this->queryService->query($request->user(), $filters, $ids, $snapshotAt)->count();
        $asyncLimit = self::ASYNC_LIMITS[$format === 'pdf' ? "pdf_{$mode}" : $format] ?? self::ASYNC_LIMITS['excel'];
        if ($total > $asyncLimit) {
            return $this->error("Export ini berisi {$total} data dan melewati batas antrean {$asyncLimit}. Persempit filter sebelum melanjutkan.", 422, ['scope' => ["Export ini berisi {$total} data dan melewati batas antrean {$asyncLimit}."]], 'VALIDATION_FAILED');
        }

        $run = ExportRun::query()->create([
            'user_id' => $request->user()->id,
            'status' => ExportRun::STATUS_PENDING,
            'format' => $format,
            'mode' => $mode,
            'profile' => $profile,
            'scope' => $scope,
            'filters' => $filters,
            'selected_ids' => $ids,
            'columns' => $columns,
            'snapshot_at' => $snapshotAt,
            'total_records' => $total,
            'disk' => 'local',
        ]);

        activity('export')->causedBy($request->user())->performedOn($run)->event('requested')
            ->withProperties(['filters' => $filters, 'columns' => $columns, 'records' => $total])->log('Export diminta');

        GeneratePembandingExport::dispatch($run->id);

        return $this->success($this->mapExportRun($run), 'Tugas ekspor berhasil didaftarkan ke antrean.', 202);
    }

    #[Endpoint(
        title: 'Lihat status tugas ekspor asinkron',
        description: 'Mengecek progres pemrosesan file ekspor (persentase selesai, URL unduh jika siap, atau pesan kegagalan).'
    )]
    public function runStatus(PembandingExportRequest $request, ExportRun $exportRun): JsonResponse
    {
        $this->authorizeRun($request->user(), $exportRun);

        return $this->success($this->mapExportRun($exportRun->fresh()), 'Status tugas ekspor.');
    }

    #[Endpoint(
        title: 'Unduh file hasil tugas ekspor asinkron',
        description: 'Mengunduh file yang telah selesai di-generate oleh worker.'
    )]
    public function downloadRun(PembandingExportRequest $request, ExportRun $exportRun): StreamedResponse
    {
        $this->authorizeRun($request->user(), $exportRun);
        abort_unless($exportRun->isDownloadable() && Storage::disk($exportRun->disk)->exists($exportRun->path), 404);

        $exportRun->update(['downloaded_at' => now()]);
        activity('export')->causedBy($request->user())->performedOn($exportRun)->event('downloaded')->log('File export diunduh');

        return Storage::disk($exportRun->disk)->download($exportRun->path, $exportRun->filename);
    }

    #[Endpoint(
        title: 'Coba ulang (retry) tugas ekspor yang gagal',
        description: 'Mengulang pembuatan file ekspor yang sebelumnya terhenti karena kegagalan proses.'
    )]
    public function retryRun(PembandingExportRequest $request, ExportRun $exportRun): JsonResponse
    {
        $this->authorizeRun($request->user(), $exportRun);
        abort_unless($exportRun->status === ExportRun::STATUS_FAILED, 409, 'Hanya ekspor berstatus gagal yang dapat diulang.');

        $exportRun->update([
            'status' => ExportRun::STATUS_PENDING,
            'started_at' => null,
            'failed_at' => null,
            'error' => null,
            'processed_records' => 0,
        ]);
        GeneratePembandingExport::dispatch($exportRun->id);

        return $this->success($this->mapExportRun($exportRun), 'Ekspor dijadwalkan ulang.');
    }

    #[Endpoint(
        title: 'Unduh ekspor langsung (sinkron)',
        description: 'Mengunduh langsung file ekspor untuk dataset di bawah batas sinkron (Excel/CSV/GeoJSON/KML/PDF).'
    )]
    public function download(PembandingExportRequest $request): BinaryFileResponse|StreamedResponse
    {
        $this->authorizePermission('export_data::pembanding');

        $format = (string) ($request->validated('format') ?? 'excel');
        $mode = (string) ($request->validated('mode') ?? 'summary');
        $scope = (string) ($request->validated('scope') ?? 'filtered');
        $filters = $this->requestFilters($request);
        $ids = $scope === 'selected' ? $this->queryService->parseIds($request->input('ids')) : [];

        if ($scope === 'selected' && $ids === []) {
            throw ValidationException::withMessages(['ids' => 'Pilih sedikitnya satu data untuk diexport.']);
        }

        $query = $this->queryService->query($request->user(), $filters, $ids);
        $count = (clone $query)->count();
        $limit = $this->limitFor($format, $mode);

        if ($count > $limit) {
            throw ValidationException::withMessages([
                'scope' => "Export {$format} {$mode} maksimal {$limit} data untuk proses langsung. Persempit filter atau gunakan pilihan data.",
            ]);
        }

        $columns = $this->columnRegistry->resolveColumns(
            $request->user(),
            $request->validated('profile'),
            $request->validated('columns') ?? [],
        );

        if ($columns === []) {
            throw ValidationException::withMessages(['columns' => 'Tidak ada kolom yang diizinkan untuk diexport.']);
        }

        $metadata = [
            'Dibuat pada' => now()->format('Y-m-d H:i:s T'),
            'Diminta oleh' => $request->user()->name,
            'Format' => strtoupper($format),
            'Mode' => $mode,
            'Profil' => $request->validated('profile') ?? PembandingExportColumnRegistry::DEFAULT_PROFILE,
            'Scope' => $scope,
            'Jumlah data' => $count,
            'Filter' => array_filter($filters, fn (mixed $value): bool => filled($value)),
        ];

        activity('export')->causedBy($request->user())->event('downloaded')
            ->withProperties([
                'format' => $format,
                'mode' => $mode,
                'profile' => $request->validated('profile') ?? PembandingExportColumnRegistry::DEFAULT_PROFILE,
                'scope' => $scope,
                'records' => $count,
                'filters' => $filters,
                'columns' => $columns,
            ])->log('Export sinkron dibuat dan diunduh');

        return $this->fileService->download($query->get(), $format, $mode, $columns, $metadata);
    }

    private function limitFor(string $format, string $mode): int
    {
        return self::LIMITS[$format === 'pdf' ? "pdf_{$mode}" : $format] ?? self::LIMITS['excel'];
    }

    private function authorizeRun(User $user, ExportRun $run): void
    {
        abort_unless((int) $run->user_id === (int) $user->id || $user->can('view_export_audit'), 403);
    }

    private function mapExportRun(ExportRun $run): array
    {
        return [
            'id' => $run->id,
            'status' => $run->status,
            'format' => $run->format,
            'mode' => $run->mode,
            'profile' => $run->profile,
            'scope' => $run->scope,
            'total_records' => $run->total_records,
            'processed_records' => $run->processed_records,
            'created_at' => $run->created_at?->toIso8601String(),
            'expires_at' => $run->expires_at?->toIso8601String(),
            'error' => $run->status === ExportRun::STATUS_FAILED ? $run->error : null,
            'download_url' => $run->isDownloadable() ? url("/api/v1/exports/runs/{$run->id}/download") : null,
        ];
    }

    private function requestFilters(PembandingExportRequest $request): array
    {
        return [
            ...$request->filters(app(PembandingBrowseFilterService::class)),
            'dataset' => (string) ($request->validated('dataset') ?? 'all'),
        ];
    }
}
