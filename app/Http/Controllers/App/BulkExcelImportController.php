<?php

namespace App\Http\Controllers\App;

use App\Actions\BulkExcelImport\BulkApplyBulkExcelImportRowsAction;
use App\Actions\BulkExcelImport\CreateBulkExcelImportBatchAction;
use App\Actions\BulkExcelImport\FinalizeBulkExcelImportBatchAction;
use App\Actions\BulkExcelImport\RetryBulkExcelImportRowAction;
use App\Actions\BulkExcelImport\UpdateBulkExcelImportRowAction;
use App\Actions\BulkExcelImport\UpdateBulkExcelImportSelectionAction;
use App\Exceptions\InvalidBulkExcelImportWorkbookException;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BulkExcelImportBulkApplyRequest;
use App\Http\Requests\App\BulkExcelImportFinalizeRequest;
use App\Http\Requests\App\BulkExcelImportRowUpdateRequest;
use App\Http\Requests\App\BulkExcelImportSelectionRequest;
use App\Http\Requests\App\BulkExcelImportStoreRequest;
use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use App\Services\Pembanding\PembandingFormOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BulkExcelImportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BulkExcelImportBatch::class);

        $batches = BulkExcelImportBatch::query()
            ->when(! $request->user()->hasRole('super_admin'), fn ($query) => $query->where('owner_id', $request->user()->id))
            ->with('owner:id,name')
            ->latest('updated_at')
            ->paginate(15)
            ->through(fn (BulkExcelImportBatch $batch): array => $this->batchPayload($batch));

        return Inertia::render('PembandingImports/Index', [
            'batches' => $batches,
        ]);
    }

    public function store(BulkExcelImportStoreRequest $request, CreateBulkExcelImportBatchAction $action): RedirectResponse
    {
        try {
            $result = $action->execute($request->user(), $request->file('file'));
        } catch (InvalidBulkExcelImportWorkbookException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $message = $result['existing']
            ? 'File ini sudah pernah diunggah. Draf sebelumnya dibuka kembali.'
            : 'File berhasil dibaca dan disimpan sebagai draf. Periksa data sebelum melanjutkan.';

        return redirect()
            ->route($this->routeName('show'), $result['batch'])
            ->with('success', $message);
    }

    public function show(
        Request $request,
        BulkExcelImportBatch $batch,
        PembandingFormOptionsService $formOptions,
    ): Response {
        Gate::authorize('view', $batch);
        $batch->load('owner:id,name');

        $filters = $request->validate([
            'status' => ['nullable', Rule::in([
                BulkExcelImportRow::STATUS_INCOMPLETE,
                BulkExcelImportRow::STATUS_NEEDS_CONFIRMATION,
                BulkExcelImportRow::STATUS_INVALID,
                BulkExcelImportRow::STATUS_DUPLICATE,
                BulkExcelImportRow::STATUS_READY,
                BulkExcelImportRow::STATUS_IMPORTED,
                BulkExcelImportRow::STATUS_FAILED,
                BulkExcelImportRow::STATUS_QUEUED,
                BulkExcelImportRow::STATUS_PROCESSING,
                BulkExcelImportRow::STATUS_FINAL_DUPLICATE,
                BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
            ])],
            'selected' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $rows = $batch->rows()
            ->with(['pembanding', 'conflictingPembanding'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(isset($filters['selected']), fn ($query) => $query->where('is_selected', $filters['selected'] === '1'))
            ->paginate(25)
            ->through(fn (BulkExcelImportRow $row): array => [
                'id' => $row->id,
                'source_row_number' => $row->source_row_number,
                'status' => $row->status,
                'status_label' => $this->rowStatusLabel($row->status),
                'is_selected' => $row->is_selected,
                'jenis_pembanding' => $row->raw_payload['Jenis Pembanding'] ?? '-',
                'alamat' => $row->mapped_payload['alamat_data'] ?? '-',
                'location' => collect([
                    $row->raw_payload['Desa'] ?? null,
                    $row->raw_payload['Kecamatan'] ?? null,
                    $row->raw_payload['Kota'] ?? null,
                    $row->raw_payload['Propinsi'] ?? null,
                ])->filter()->implode(', '),
                'missing_fields' => $row->missing_fields ?? [],
                'warnings' => $row->warnings ?? [],
                'has_image' => $row->staging_image_path !== null,
                'last_error' => $row->last_error,
                'failure_code' => $row->failure_code,
                'result_url' => $this->resultUrl($request, $row),
                'retry_url' => $this->retryUrl($batch, $row),
                'edit_url' => ! $batch->allowsDraftChanges() || in_array($row->status, [
                    BulkExcelImportRow::STATUS_DUPLICATE,
                    BulkExcelImportRow::STATUS_FINAL_DUPLICATE,
                    BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                    BulkExcelImportRow::STATUS_QUEUED,
                    BulkExcelImportRow::STATUS_PROCESSING,
                ], true)
                    ? null
                    : route($this->routeName('rows.edit'), [$batch, $row]),
            ]);

        return Inertia::render('PembandingImports/Show', [
            'batch' => $this->batchPayload($batch),
            'rows' => $rows,
            'filters' => $filters,
            'options' => fn (): array => Arr::only($formOptions->for(), [
                'statusPemberiInfos',
                'bentukTanahs',
                'posisiTanahs',
                'kondisiTanahs',
                'topografis',
                'dokumenTanahs',
                'peruntukans',
            ]),
        ]);
    }

    public function bulkApply(
        BulkExcelImportBulkApplyRequest $request,
        BulkExcelImportBatch $batch,
        BulkApplyBulkExcelImportRowsAction $action,
    ): RedirectResponse {
        $validated = $request->validated();
        $updated = $action->execute($batch, $validated['field'], (int) $validated['value']);

        return back()->with(
            'success',
            $updated === 0
                ? 'Tidak ada data terpilih yang dapat diubah.'
                : "Nilai berhasil diterapkan ke {$updated} data terpilih.",
        );
    }

    public function edit(
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        PembandingFormOptionsService $formOptions,
    ): Response {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);

        if (! $batch->allowsDraftChanges()
            || in_array($row->status, [
                BulkExcelImportRow::STATUS_DUPLICATE,
                BulkExcelImportRow::STATUS_FINAL_DUPLICATE,
                BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                BulkExcelImportRow::STATUS_QUEUED,
                BulkExcelImportRow::STATUS_PROCESSING,
            ], true)
            || $row->pembanding_id !== null) {
            abort(404);
        }

        $payload = $row->mapped_payload ?? [];
        $previous = $batch->rows()
            ->where('status', '!=', BulkExcelImportRow::STATUS_DUPLICATE)
            ->whereNull('pembanding_id')
            ->where('source_row_number', '<', $row->source_row_number)
            ->orderByDesc('source_row_number')
            ->first();
        $next = $batch->rows()
            ->where('status', '!=', BulkExcelImportRow::STATUS_DUPLICATE)
            ->whereNull('pembanding_id')
            ->where('source_row_number', '>', $row->source_row_number)
            ->orderBy('source_row_number')
            ->first();

        return Inertia::render('PembandingImports/Edit', [
            'batch' => $this->batchPayload($batch->load('owner:id,name')),
            'row' => [
                'id' => $row->id,
                'source_row_number' => $row->source_row_number,
                'status' => $row->status,
                'data' => $payload,
                'missing_fields' => $row->missing_fields ?? [],
                'warnings' => $row->warnings ?? [],
                'image_url' => $row->staging_image_path
                    ? route($this->routeName('rows.image'), [$batch, $row])
                    : null,
            ],
            'options' => $formOptions->for($payload),
            'navigation' => [
                'previous_url' => $previous ? route($this->routeName('rows.edit'), [$batch, $previous]) : null,
                'next_url' => $next ? route($this->routeName('rows.edit'), [$batch, $next]) : null,
            ],
        ]);
    }

    public function update(
        BulkExcelImportRowUpdateRequest $request,
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        UpdateBulkExcelImportRowAction $action,
    ): RedirectResponse {
        $this->ensureRowBelongsToBatch($batch, $row);
        $updated = $action->execute($row, $request->validated(), $request->file('image'));

        $message = $updated->status === BulkExcelImportRow::STATUS_READY
            ? 'Draf tersimpan dan data ini sudah lengkap.'
            : 'Draf tersimpan. Lengkapi bagian yang masih ditandai.';

        return redirect()
            ->route($this->routeName('rows.edit'), [$batch, $updated])
            ->with('success', $message);
    }

    public function image(BulkExcelImportBatch $batch, BulkExcelImportRow $row)
    {
        Gate::authorize('view', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);

        if (! $row->staging_image_path) {
            abort(404);
        }

        $disk = Storage::disk($row->staging_image_disk ?: 'local');
        if (! $disk->exists($row->staging_image_path)) {
            abort(404);
        }

        return $disk->response(
            $row->staging_image_path,
            $row->staging_image_original_name,
            [
                'Content-Type' => $row->staging_image_mime ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function selection(
        BulkExcelImportSelectionRequest $request,
        BulkExcelImportBatch $batch,
        UpdateBulkExcelImportSelectionAction $action,
    ): RedirectResponse {
        $validated = $request->validated();
        $action->execute(
            $batch,
            $validated['action'],
            $validated['row_ids'] ?? [],
            (bool) ($validated['is_selected'] ?? false),
        );

        return back()->with('success', 'Pilihan data berhasil disimpan.');
    }

    public function finalize(
        BulkExcelImportFinalizeRequest $request,
        BulkExcelImportBatch $batch,
        FinalizeBulkExcelImportBatchAction $action,
    ): RedirectResponse {
        $action->execute($batch, $request->user());

        return back()->with('success', 'Data mulai dimasukkan. Halaman ini akan memperbarui hasil secara otomatis.');
    }

    public function retry(
        Request $request,
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        RetryBulkExcelImportRowAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);
        $action->execute($batch, $row);

        return back()->with('success', 'Data akan dicoba kembali. Hasil akan diperbarui otomatis.');
    }

    private function batchPayload(BulkExcelImportBatch $batch): array
    {
        $processingRows = $batch->rows()
            ->reorder()
            ->where('is_selected', true)
            ->whereIn('status', [BulkExcelImportRow::STATUS_QUEUED, BulkExcelImportRow::STATUS_PROCESSING])
            ->count();
        $canEdit = $batch->status === BulkExcelImportBatch::STATUS_DRAFT;
        $canFinalize = $batch->status === BulkExcelImportBatch::STATUS_DRAFT
            && $batch->selected_rows > 0
            && $batch->selected_rows === $batch->ready_rows;

        return [
            'id' => $batch->id,
            'filename' => $batch->original_filename,
            'owner' => $batch->owner?->name,
            'status' => $batch->status,
            'status_label' => $this->batchStatusLabel($batch->status),
            'total_rows' => $batch->total_rows,
            'selected_rows' => $batch->selected_rows,
            'ready_rows' => $batch->ready_rows,
            'imported_rows' => $batch->imported_rows,
            'failed_rows' => $batch->failed_rows,
            'processing_rows' => $processingRows,
            'unselected_rows' => max(0, $batch->total_rows - $batch->selected_rows),
            'can_edit' => $canEdit,
            'can_finalize' => $canFinalize,
            'finalize_block_reason' => $this->finalizeBlockReason($batch),
            'finalization_date' => optional($batch->finalization_date)->format('Y-m-d'),
            'finalized_at' => optional($batch->finalized_at)->toDateTimeString(),
            'updated_at' => optional($batch->updated_at)->toDateTimeString(),
        ];
    }

    private function rowStatusLabel(string $status): string
    {
        return match ($status) {
            BulkExcelImportRow::STATUS_DUPLICATE => 'Data sama',
            BulkExcelImportRow::STATUS_NEEDS_CONFIRMATION => 'Perlu diperiksa',
            BulkExcelImportRow::STATUS_INVALID => 'Perlu diperbaiki',
            BulkExcelImportRow::STATUS_READY => 'Siap dimasukkan',
            BulkExcelImportRow::STATUS_QUEUED, BulkExcelImportRow::STATUS_PROCESSING => 'Sedang diproses',
            BulkExcelImportRow::STATUS_IMPORTED => 'Berhasil dimasukkan',
            BulkExcelImportRow::STATUS_FAILED => 'Perlu diperbaiki',
            BulkExcelImportRow::STATUS_FINAL_DUPLICATE => 'Sudah ada di Data Pembanding',
            BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED => 'Sumber sudah pernah dimasukkan',
            default => 'Belum lengkap',
        };
    }

    private function batchStatusLabel(string $status): string
    {
        return match ($status) {
            BulkExcelImportBatch::STATUS_PROCESSING => 'Sedang dimasukkan',
            BulkExcelImportBatch::STATUS_COMPLETE => 'Selesai',
            BulkExcelImportBatch::STATUS_PARTIAL => 'Sebagian perlu diperbaiki',
            BulkExcelImportBatch::STATUS_FAILED => 'Perlu diperbaiki',
            default => 'Draf',
        };
    }

    private function finalizeBlockReason(BulkExcelImportBatch $batch): ?string
    {
        if ($batch->status !== BulkExcelImportBatch::STATUS_DRAFT) {
            return null;
        }

        if ($batch->selected_rows === 0) {
            return 'Pilih setidaknya satu data terlebih dahulu.';
        }

        $unfinished = max(0, $batch->selected_rows - $batch->ready_rows);

        return $unfinished > 0 ? "Masih ada {$unfinished} data terpilih yang belum lengkap." : null;
    }

    private function resultUrl(Request $request, BulkExcelImportRow $row): ?string
    {
        $pembanding = $row->pembanding ?: $row->conflictingPembanding;

        return $pembanding && ! $pembanding->trashed() && $request->user()->can('view', $pembanding)
            ? route('app.pembanding.show', $pembanding)
            : null;
    }

    private function retryUrl(BulkExcelImportBatch $batch, BulkExcelImportRow $row): ?string
    {
        $retryableBatch = in_array($batch->status, [BulkExcelImportBatch::STATUS_PARTIAL, BulkExcelImportBatch::STATUS_FAILED], true);
        $retryableRow = $row->status === BulkExcelImportRow::STATUS_READY
            || ($row->status === BulkExcelImportRow::STATUS_FAILED && $row->failure_code === 'transient');

        return $retryableBatch && $retryableRow
            ? route($this->routeName('rows.retry'), [$batch, $row])
            : null;
    }

    private function routeName(string $suffix): string
    {
        return 'app.bulk-excel-imports.'.$suffix;
    }

    private function ensureRowBelongsToBatch(BulkExcelImportBatch $batch, BulkExcelImportRow $row): void
    {
        abort_unless($row->batch_id === $batch->id, 404);
    }
}
