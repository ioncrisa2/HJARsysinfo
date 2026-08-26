<?php

namespace App\Http\Controllers\Api;

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
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Impor Excel Pembanding', 'Alur impor data pembanding massal bertahap melalui spreadsheet Excel.', weight: 6)]
class BulkExcelImportController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat daftar batch impor Excel',
        description: 'Mengembalikan riwayat batch impor Excel terpaginasi milik pengguna aktif (atau semua batch untuk super admin).'
    )]
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', BulkExcelImportBatch::class);

        $batches = BulkExcelImportBatch::query()
            ->when(! $request->user()->hasRole('super_admin'), fn ($query) => $query->where('owner_id', $request->user()->id))
            ->with('owner:id,name')
            ->latest('updated_at')
            ->paginate((int) $request->integer('per_page', 15));

        $data = collect($batches->items())->map(fn (BulkExcelImportBatch $batch): array => $this->batchPayload($batch))->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar batch impor Excel berhasil diambil.',
            'data' => $data,
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'from' => $batches->firstItem(),
                'to' => $batches->lastItem(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
            'links' => [
                'first' => $batches->url(1),
                'last' => $batches->url($batches->lastPage()),
                'prev' => $batches->previousPageUrl(),
                'next' => $batches->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Upload workbook Excel impor baru',
        description: 'Menerima file .xlsx/.xls, memvalidasi struktur kolom, dan membuat batch draf baru (atau membuka kembali draf yang sama).'
    )]
    public function store(BulkExcelImportStoreRequest $request, CreateBulkExcelImportBatchAction $action): JsonResponse
    {
        try {
            $result = $action->execute($request->user(), $request->file('file'));
        } catch (InvalidBulkExcelImportWorkbookException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $message = $result['existing']
            ? 'File ini sudah pernah diunggah. Draf sebelumnya dibuka kembali.'
            : 'File berhasil dibaca dan disimpan sebagai draf. Periksa data sebelum melanjutkan.';

        return $this->success([
            'batch' => $this->batchPayload($result['batch']),
            'is_existing' => (bool) $result['existing'],
        ], $message, 201);
    }

    #[Endpoint(
        title: 'Lihat detail batch impor dan baris staged',
        description: 'Mengembalikan statistik batch beserta daftar baris impor terpaginasi dengan filter status dan seleksi.'
    )]
    public function show(
        Request $request,
        BulkExcelImportBatch $batch,
        PembandingFormOptionsService $formOptions,
    ): JsonResponse {
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

        $perPage = (int) $request->integer('per_page', 25);
        $rows = $batch->rows()
            ->with(['pembanding', 'conflictingPembanding'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(isset($filters['selected']), fn ($query) => $query->where('is_selected', $filters['selected'] === '1'))
            ->paginate($perPage);

        $rowItems = collect($rows->items())->map(fn (BulkExcelImportRow $row): array => [
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
            'image_url' => $row->staging_image_path ? url("/api/v1/pembanding-imports/{$batch->id}/rows/{$row->id}/image") : null,
            'last_error' => $row->last_error,
            'failure_code' => $row->failure_code,
            'result_url' => $this->resultUrl($request, $row),
        ])->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Detail batch impor berhasil diambil.',
            'batch' => $this->batchPayload($batch),
            'data' => $rowItems,
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
            'links' => [
                'first' => $rows->url(1),
                'last' => $rows->url($rows->lastPage()),
                'prev' => $rows->previousPageUrl(),
                'next' => $rows->nextPageUrl(),
            ],
            'options' => Arr::only($formOptions->for(), [
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

    #[Endpoint(
        title: 'Perbarui pilihan baris batch impor',
        description: 'Memilih atau membatalkan pilihan baris staged dalam batch (all, none, invert, atau ID spesifik).'
    )]
    public function selection(
        BulkExcelImportSelectionRequest $request,
        BulkExcelImportBatch $batch,
        UpdateBulkExcelImportSelectionAction $action,
    ): JsonResponse {
        $validated = $request->validated();
        $action->execute(
            $batch,
            $validated['action'],
            $validated['row_ids'] ?? [],
            (bool) ($validated['is_selected'] ?? false),
        );

        $batch->refresh();

        return $this->success($this->batchPayload($batch), 'Pilihan data berhasil disimpan.');
    }

    #[Endpoint(
        title: 'Terapkan nilai massal ke baris terpilih',
        description: 'Mengisi atribut master data (status pemberi info, bentuk tanah, peruntukan, dsb.) sekaligus ke seluruh baris terpilih.'
    )]
    public function bulkApply(
        BulkExcelImportBulkApplyRequest $request,
        BulkExcelImportBatch $batch,
        BulkApplyBulkExcelImportRowsAction $action,
    ): JsonResponse {
        $validated = $request->validated();
        $updated = $action->execute($batch, $validated['field'], (int) $validated['value']);

        return $this->success([
            'updated_rows' => $updated,
            'batch' => $this->batchPayload($batch->refresh()),
        ], $updated === 0
            ? 'Tidak ada data terpilih yang dapat diubah.'
            : "Nilai berhasil diterapkan ke {$updated} data terpilih.");
    }

    #[Endpoint(
        title: 'Lihat data satu baris staged untuk edit',
        description: 'Mengembalikan payload detail baris staged beserta opsi dropdown lokasi dan dictionary terkait.'
    )]
    public function editRow(
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        PembandingFormOptionsService $formOptions,
    ): JsonResponse {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);

        $payload = $row->mapped_payload ?? [];

        return $this->success([
            'row' => [
                'id' => $row->id,
                'source_row_number' => $row->source_row_number,
                'status' => $row->status,
                'status_label' => $this->rowStatusLabel($row->status),
                'data' => $payload,
                'raw_payload' => $row->raw_payload,
                'missing_fields' => $row->missing_fields ?? [],
                'warnings' => $row->warnings ?? [],
                'image_url' => $row->staging_image_path
                    ? url("/api/v1/pembanding-imports/{$batch->id}/rows/{$row->id}/image")
                    : null,
            ],
            'options' => $formOptions->for($payload),
        ], 'Detail baris impor berhasil diambil.');
    }

    #[Endpoint(
        title: 'Perbarui baris staged',
        description: 'Memperbaiki field yang kurang atau mengganti foto pada baris staged.'
    )]
    public function updateRow(
        BulkExcelImportRowUpdateRequest $request,
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        UpdateBulkExcelImportRowAction $action,
    ): JsonResponse {
        $this->ensureRowBelongsToBatch($batch, $row);
        $updated = $action->execute($row, $request->validated(), $request->file('image'));

        $message = $updated->status === BulkExcelImportRow::STATUS_READY
            ? 'Draf tersimpan dan data ini sudah lengkap.'
            : 'Draf tersimpan. Lengkapi bagian yang masih ditandai.';

        return $this->success([
            'row' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'status_label' => $this->rowStatusLabel($updated->status),
                'missing_fields' => $updated->missing_fields ?? [],
                'warnings' => $updated->warnings ?? [],
            ],
            'batch' => $this->batchPayload($batch->refresh()),
        ], $message);
    }

    #[Endpoint(
        title: 'Stream gambar baris staged',
        description: 'Mengalirkan binary foto properti yang tersimpan pada baris impor draf.'
    )]
    public function rowImage(BulkExcelImportBatch $batch, BulkExcelImportRow $row): StreamedResponse
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

    #[Endpoint(
        title: 'Finalisasi batch impor (migrasikan ke data pembanding)',
        description: 'Menjalankan proses finalisasi asinkron untuk memasukkan seluruh baris terpilih yang berstatus siap ke tabel data pembanding utama.'
    )]
    public function finalize(
        BulkExcelImportFinalizeRequest $request,
        BulkExcelImportBatch $batch,
        FinalizeBulkExcelImportBatchAction $action,
    ): JsonResponse {
        $action->execute($batch, $request->user());

        return $this->success([
            'batch' => $this->batchPayload($batch->refresh()),
        ], 'Data mulai dimasukkan. Silakan pantau status pemrosesan.');
    }

    #[Endpoint(
        title: 'Coba ulang (retry) baris impor yang gagal',
        description: 'Mengulang validasi atau proses impor untuk baris yang sebelumnya berstatus gagal/transient error.'
    )]
    public function retryRow(
        Request $request,
        BulkExcelImportBatch $batch,
        BulkExcelImportRow $row,
        RetryBulkExcelImportRowAction $action,
    ): JsonResponse {
        Gate::authorize('update', $batch);
        $this->ensureRowBelongsToBatch($batch, $row);
        $action->execute($batch, $row);

        return $this->success([
            'row' => [
                'id' => $row->id,
                'status' => $row->refresh()->status,
                'status_label' => $this->rowStatusLabel($row->status),
            ],
            'batch' => $this->batchPayload($batch->refresh()),
        ], 'Data akan dicoba kembali.');
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
            ? url("/api/v1/pembandings/{$pembanding->id}")
            : null;
    }

    private function ensureRowBelongsToBatch(BulkExcelImportBatch $batch, BulkExcelImportRow $row): void
    {
        abort_unless($row->batch_id === $batch->id, 404);
    }
}
