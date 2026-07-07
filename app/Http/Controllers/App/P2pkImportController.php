<?php

namespace App\Http\Controllers\App;

use App\Actions\P2pk\BulkApplyP2pkImportRowsAction;
use App\Actions\P2pk\CreateP2pkImportBatchAction;
use App\Actions\P2pk\FinalizeP2pkImportBatchAction;
use App\Actions\P2pk\RetryP2pkImportRowAction;
use App\Actions\P2pk\UpdateP2pkImportRowAction;
use App\Actions\P2pk\UpdateP2pkImportSelectionAction;
use App\Exceptions\InvalidP2pkWorkbookException;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\P2pkImportBulkApplyRequest;
use App\Http\Requests\App\P2pkImportFinalizeRequest;
use App\Http\Requests\App\P2pkImportRowUpdateRequest;
use App\Http\Requests\App\P2pkImportSelectionRequest;
use App\Http\Requests\App\P2pkImportStoreRequest;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
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

class P2pkImportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', P2pkImportBatch::class);

        $batches = P2pkImportBatch::query()
            ->when(! $request->user()->hasRole('super_admin'), fn ($query) => $query->where('owner_id', $request->user()->id))
            ->with('owner:id,name')
            ->latest('updated_at')
            ->paginate(15)
            ->through(fn (P2pkImportBatch $batch): array => $this->batchPayload($batch));

        return Inertia::render('PembandingImports/Index', [
            'batches' => $batches,
            'importContext' => $this->importContext(),
        ]);
    }

    public function store(P2pkImportStoreRequest $request, CreateP2pkImportBatchAction $action): RedirectResponse
    {
        try {
            $result = $action->execute($request->user(), $request->file('file'));
        } catch (InvalidP2pkWorkbookException $exception) {
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
        P2pkImportBatch $batch,
        PembandingFormOptionsService $formOptions,
    ): Response {
        Gate::authorize('view', $batch);
        $batch->load('owner:id,name');

        $filters = $request->validate([
            'status' => ['nullable', Rule::in([
                P2pkImportRow::STATUS_INCOMPLETE,
                P2pkImportRow::STATUS_NEEDS_CONFIRMATION,
                P2pkImportRow::STATUS_INVALID,
                P2pkImportRow::STATUS_DUPLICATE,
                P2pkImportRow::STATUS_READY,
                P2pkImportRow::STATUS_IMPORTED,
                P2pkImportRow::STATUS_FAILED,
                P2pkImportRow::STATUS_QUEUED,
                P2pkImportRow::STATUS_PROCESSING,
                P2pkImportRow::STATUS_FINAL_DUPLICATE,
                P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
            ])],
            'selected' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $rows = $batch->rows()
            ->with(['pembanding', 'conflictingPembanding'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(isset($filters['selected']), fn ($query) => $query->where('is_selected', $filters['selected'] === '1'))
            ->paginate(25)
            ->through(fn (P2pkImportRow $row): array => [
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
                    P2pkImportRow::STATUS_DUPLICATE,
                    P2pkImportRow::STATUS_FINAL_DUPLICATE,
                    P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                    P2pkImportRow::STATUS_QUEUED,
                    P2pkImportRow::STATUS_PROCESSING,
                ], true)
                    ? null
                    : route($this->routeName('rows.edit'), [$batch, $row]),
            ]);

        return Inertia::render('PembandingImports/Show', [
            'batch' => $this->batchPayload($batch),
            'rows' => $rows,
            'filters' => $filters,
            'importContext' => $this->importContext(),
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
        P2pkImportBulkApplyRequest $request,
        P2pkImportBatch $batch,
        BulkApplyP2pkImportRowsAction $action,
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
        P2pkImportBatch $batch,
        P2pkImportRow $row,
        PembandingFormOptionsService $formOptions,
    ): Response {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);

        if (! $batch->allowsDraftChanges()
            || in_array($row->status, [
                P2pkImportRow::STATUS_DUPLICATE,
                P2pkImportRow::STATUS_FINAL_DUPLICATE,
                P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                P2pkImportRow::STATUS_QUEUED,
                P2pkImportRow::STATUS_PROCESSING,
            ], true)
            || $row->pembanding_id !== null) {
            abort(404);
        }

        $payload = $row->mapped_payload ?? [];
        $previous = $batch->rows()
            ->where('status', '!=', P2pkImportRow::STATUS_DUPLICATE)
            ->whereNull('pembanding_id')
            ->where('source_row_number', '<', $row->source_row_number)
            ->orderByDesc('source_row_number')
            ->first();
        $next = $batch->rows()
            ->where('status', '!=', P2pkImportRow::STATUS_DUPLICATE)
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
            'importContext' => $this->importContext(),
        ]);
    }

    public function update(
        P2pkImportRowUpdateRequest $request,
        P2pkImportBatch $batch,
        P2pkImportRow $row,
        UpdateP2pkImportRowAction $action,
    ): RedirectResponse {
        $this->ensureRowBelongsToBatch($batch, $row);
        $updated = $action->execute($row, $request->validated(), $request->file('image'));

        $message = $updated->status === P2pkImportRow::STATUS_READY
            ? 'Draf tersimpan dan data ini sudah lengkap.'
            : 'Draf tersimpan. Lengkapi bagian yang masih ditandai.';

        return redirect()
            ->route($this->routeName('rows.edit'), [$batch, $updated])
            ->with('success', $message);
    }

    public function image(P2pkImportBatch $batch, P2pkImportRow $row)
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
        P2pkImportSelectionRequest $request,
        P2pkImportBatch $batch,
        UpdateP2pkImportSelectionAction $action,
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
        P2pkImportFinalizeRequest $request,
        P2pkImportBatch $batch,
        FinalizeP2pkImportBatchAction $action,
    ): RedirectResponse {
        $action->execute($batch, $request->user());

        return back()->with('success', 'Data mulai dimasukkan. Halaman ini akan memperbarui hasil secara otomatis.');
    }

    public function retry(
        Request $request,
        P2pkImportBatch $batch,
        P2pkImportRow $row,
        RetryP2pkImportRowAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);
        $action->execute($batch, $row);

        return back()->with('success', 'Data akan dicoba kembali. Hasil akan diperbarui otomatis.');
    }

    private function batchPayload(P2pkImportBatch $batch): array
    {
        $processingRows = $batch->rows()
            ->reorder()
            ->where('is_selected', true)
            ->whereIn('status', [P2pkImportRow::STATUS_QUEUED, P2pkImportRow::STATUS_PROCESSING])
            ->count();
        $canEdit = $batch->status === P2pkImportBatch::STATUS_DRAFT;
        $canFinalize = $batch->status === P2pkImportBatch::STATUS_DRAFT
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
            P2pkImportRow::STATUS_DUPLICATE => 'Data sama',
            P2pkImportRow::STATUS_NEEDS_CONFIRMATION => 'Perlu diperiksa',
            P2pkImportRow::STATUS_INVALID => 'Perlu diperbaiki',
            P2pkImportRow::STATUS_READY => 'Siap dimasukkan',
            P2pkImportRow::STATUS_QUEUED, P2pkImportRow::STATUS_PROCESSING => 'Sedang diproses',
            P2pkImportRow::STATUS_IMPORTED => 'Berhasil dimasukkan',
            P2pkImportRow::STATUS_FAILED => 'Perlu diperbaiki',
            P2pkImportRow::STATUS_FINAL_DUPLICATE => 'Sudah ada di Data Pembanding',
            P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED => 'Sumber sudah pernah dimasukkan',
            default => 'Belum lengkap',
        };
    }

    private function batchStatusLabel(string $status): string
    {
        return match ($status) {
            P2pkImportBatch::STATUS_PROCESSING => 'Sedang dimasukkan',
            P2pkImportBatch::STATUS_COMPLETE => 'Selesai',
            P2pkImportBatch::STATUS_PARTIAL => 'Sebagian perlu diperbaiki',
            P2pkImportBatch::STATUS_FAILED => 'Perlu diperbaiki',
            default => 'Draf',
        };
    }

    private function finalizeBlockReason(P2pkImportBatch $batch): ?string
    {
        if ($batch->status !== P2pkImportBatch::STATUS_DRAFT) {
            return null;
        }

        if ($batch->selected_rows === 0) {
            return 'Pilih setidaknya satu data terlebih dahulu.';
        }

        $unfinished = max(0, $batch->selected_rows - $batch->ready_rows);

        return $unfinished > 0 ? "Masih ada {$unfinished} data terpilih yang belum lengkap." : null;
    }

    private function resultUrl(Request $request, P2pkImportRow $row): ?string
    {
        $pembanding = $row->pembanding ?: $row->conflictingPembanding;

        return $pembanding && ! $pembanding->trashed() && $request->user()->can('view', $pembanding)
            ? route($this->isAdminContext() ? 'admin.pembanding.show' : 'home.pembanding.show', $pembanding)
            : null;
    }

    private function retryUrl(P2pkImportBatch $batch, P2pkImportRow $row): ?string
    {
        $retryableBatch = in_array($batch->status, [P2pkImportBatch::STATUS_PARTIAL, P2pkImportBatch::STATUS_FAILED], true);
        $retryableRow = $row->status === P2pkImportRow::STATUS_READY
            || ($row->status === P2pkImportRow::STATUS_FAILED && $row->failure_code === 'transient');

        return $retryableBatch && $retryableRow
            ? route($this->routeName('rows.retry'), [$batch, $row])
            : null;
    }

    private function importContext(): array
    {
        return [
            'is_admin' => $this->isAdminContext(),
            'base_url' => $this->isAdminContext()
                ? '/admin/pembanding-imports'
                : '/home/pembanding-imports',
        ];
    }

    private function routeName(string $suffix): string
    {
        return ($this->isAdminContext() ? 'admin' : 'home').'.p2pk-imports.'.$suffix;
    }

    private function isAdminContext(): bool
    {
        return request()->routeIs('admin.p2pk-imports.*');
    }

    private function ensureRowBelongsToBatch(P2pkImportBatch $batch, P2pkImportRow $row): void
    {
        abort_unless($row->batch_id === $batch->id, 404);
    }
}
