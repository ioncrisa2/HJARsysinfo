<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingExportRequest;
use App\Jobs\GeneratePembandingExport;
use App\Models\District;
use App\Models\ExportRun;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\Pembanding;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Services\Exports\PembandingExportFileService;
use App\Services\Exports\PembandingExportQueryService;
use App\Services\Pembanding\PembandingBrowseFilterService;
use App\Support\AppAccess;
use App\Support\Exports\PembandingExportColumnRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
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

    public function index(PembandingExportRequest $request): InertiaResponse
    {
        $this->authorizePermission('view_export');

        $filters = $this->requestFilters($request);
        $query = $this->queryService->query($request->user(), $filters);
        $perPage = (int) ($request->validated('per_page') ?? 25);

        $records = (clone $query)
            ->paginate($perPage)
            ->through(fn (Pembanding $record): array => $this->mapRecord($record))
            ->withQueryString();

        return Inertia::render('Export/Index', [
            'records' => $records,
            'filters' => [...$filters, 'per_page' => $perPage],
            'options' => $this->options($filters, $request->user()),
            'exportConfiguration' => $this->columnRegistry->publicConfiguration($request->user()),
            'exportRuns' => $request->user()->exportRuns()->latest()->limit(10)->get()->map(fn (ExportRun $run): array => $this->mapExportRun($run)),
            'summary' => $this->summary($query),
            'can' => AppAccess::capabilityMap($request->user(), [
                'download' => 'export_data::pembanding',
                'sensitive' => 'export_sensitive_data::pembanding',
            ]),
        ]);
    }

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

        return response()->json([
            'count' => $count,
            'sync_limit' => $limit,
            'queued' => $count > $limit,
            'without_coordinates' => (clone $query)->where(function ($builder): void {
                $builder->whereNull('latitude')->orWhereNull('longitude');
            })->count(),
        ]);
    }

    public function store(PembandingExportRequest $request): RedirectResponse
    {
        $this->authorizePermission('export_data::pembanding');

        $format = (string) ($request->validated('format') ?? 'excel');
        $mode = (string) ($request->validated('mode') ?? 'summary');
        $profile = (string) ($request->validated('profile') ?? PembandingExportColumnRegistry::DEFAULT_PROFILE);
        $scope = (string) ($request->validated('scope') ?? 'filtered');
        $filters = $this->requestFilters($request);
        $ids = $scope === 'selected' ? $this->queryService->parseIds($request->input('ids')) : [];

        if ($scope === 'selected' && $ids === []) {
            throw ValidationException::withMessages(['ids' => 'Pilih sedikitnya satu data untuk diexport.']);
        }

        $columns = $this->columnRegistry->resolveColumns($request->user(), $profile, $request->validated('columns') ?? []);
        if ($columns === []) {
            throw ValidationException::withMessages(['columns' => 'Tidak ada kolom yang diizinkan untuk diexport.']);
        }

        $snapshotAt = now();
        $total = $this->queryService->query($request->user(), $filters, $ids, $snapshotAt)->count();
        $asyncLimit = self::ASYNC_LIMITS[$format === 'pdf' ? "pdf_{$mode}" : $format] ?? self::ASYNC_LIMITS['excel'];
        if ($total > $asyncLimit) {
            throw ValidationException::withMessages([
                'scope' => "Export ini berisi {$total} data dan melewati batas antrean {$asyncLimit}. Persempit filter sebelum melanjutkan.",
            ]);
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

        return back()->with('success', 'Export masuk antrean. Statusnya dapat dipantau pada riwayat export.');
    }

    public function status(PembandingExportRequest $request, ExportRun $exportRun): JsonResponse
    {
        $this->authorizeRun($request->user(), $exportRun);

        return response()->json($this->mapExportRun($exportRun->fresh()));
    }

    public function downloadRun(PembandingExportRequest $request, ExportRun $exportRun): StreamedResponse
    {
        $this->authorizeRun($request->user(), $exportRun);
        abort_unless($exportRun->isDownloadable() && Storage::disk($exportRun->disk)->exists($exportRun->path), 404);

        $exportRun->update(['downloaded_at' => now()]);
        activity('export')->causedBy($request->user())->performedOn($exportRun)->event('downloaded')->log('File export diunduh');

        return Storage::disk($exportRun->disk)->download($exportRun->path, $exportRun->filename);
    }

    public function retry(PembandingExportRequest $request, ExportRun $exportRun): RedirectResponse
    {
        $this->authorizeRun($request->user(), $exportRun);
        abort_unless($exportRun->status === ExportRun::STATUS_FAILED, 409);

        $exportRun->update([
            'status' => ExportRun::STATUS_PENDING,
            'started_at' => null,
            'failed_at' => null,
            'error' => null,
            'processed_records' => 0,
        ]);
        GeneratePembandingExport::dispatch($exportRun->id);

        return back()->with('success', 'Export dijadwalkan ulang.');
    }

    private function limitFor(string $format, string $mode): int
    {
        return self::LIMITS[$format === 'pdf' ? "pdf_{$mode}" : $format] ?? self::LIMITS['excel'];
    }

    private function summary($query): array
    {
        return [
            'total' => (clone $query)->count(),
            'limits' => self::LIMITS,
            'max_export_rows' => self::LIMITS['excel'],
            'without_coordinates' => (clone $query)->where(function ($builder): void {
                $builder->whereNull('latitude')->orWhereNull('longitude');
            })->count(),
            'without_photo' => (clone $query)->where(function ($builder): void {
                $builder->whereNull('image')->orWhere('image', '');
            })->count(),
            'without_price' => (clone $query)->where(function ($builder): void {
                $builder->whereNull('harga')->orWhere('harga', '<=', 0);
            })->count(),
            'stale' => (clone $query)->whereDate('tanggal_data', '<', now()->subYears(2)->toDateString())->count(),
            'inactive_references' => (clone $query)->where(function ($issues): void {
                foreach (['jenisListing', 'jenisObjek', 'statusPemberiInformasi', 'bentukTanah', 'dokumenTanah', 'posisiTanah', 'kondisiTanah', 'topografiRef', 'peruntukanRef'] as $relation) {
                    $issues->orWhereHas($relation, fn ($reference) => $reference->where('is_active', false));
                }
            })->count(),
        ];
    }

    private function options(array $filters, User $user): array
    {
        return [
            'provinces' => $this->mapSelectOptions(Province::query()->orderBy('name')->get()),
            'regencies' => $filters['province_id']
                ? $this->mapSelectOptions(Regency::query()->where('province_id', $filters['province_id'])->orderBy('name')->get()) : [],
            'districts' => $filters['regency_id']
                ? $this->mapSelectOptions(District::query()->where('regency_id', $filters['regency_id'])->orderBy('name')->get()) : [],
            'villages' => $filters['district_id']
                ? $this->mapSelectOptions(Village::query()->where('district_id', $filters['district_id'])->orderBy('name')->get()) : [],
            'jenisListings' => $this->mapSelectOptions(JenisListing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()),
            'jenisObjeks' => $this->mapSelectOptions(JenisObjek::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()),
            'creators' => $user->can('view_any_data::pembanding')
                ? $this->mapSelectOptions(User::query()
                    ->whereIn('id', Pembanding::query()->select('created_by')->whereNotNull('created_by'))
                    ->orderBy('name')->get())
                : [],
        ];
    }

    private function mapRecord(Pembanding $record): array
    {
        return [
            'id' => $record->id,
            'nama_pemberi_informasi' => $record->nama_pemberi_informasi,
            'alamat_data' => $record->alamat_data,
            'harga' => $record->harga,
            'is_sewa' => $record->is_sewa,
            'jangka_waktu_sewa' => $record->jangka_waktu_sewa,
            'satuan_waktu_sewa' => $record->satuan_waktu_sewa,
            'sewa_periode_label' => $record->sewa_periode_label,
            'tanggal_data' => $record->tanggal_data,
            'luas_tanah' => $record->luas_tanah,
            'luas_bangunan' => $record->luas_bangunan,
            'image_url' => $record->image ? Storage::disk('public')->url($record->image) : null,
            'jenis_listing' => $record->jenisListing?->name,
            'jenis_objek' => $record->jenisObjek?->name,
            'province' => $record->province?->name,
            'regency' => $record->regency?->name,
            'district' => $record->district?->name,
            'village' => $record->village?->name,
        ];
    }

    private function mapSelectOptions(Collection $items): array
    {
        return $items->map(fn ($item): array => ['label' => (string) $item->name, 'value' => $item->id])->values()->all();
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
            'download_url' => $run->isDownloadable() ? route('app.export.runs.download', $run) : null,
            'retry_url' => $run->status === ExportRun::STATUS_FAILED ? route('app.export.runs.retry', $run) : null,
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
