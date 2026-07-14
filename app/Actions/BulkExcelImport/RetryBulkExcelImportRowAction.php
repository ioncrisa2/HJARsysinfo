<?php

namespace App\Actions\BulkExcelImport;

use App\Jobs\ProcessBulkExcelImportChunk;
use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RetryBulkExcelImportRowAction
{
    public function execute(BulkExcelImportBatch $batch, BulkExcelImportRow $row): void
    {
        DB::transaction(function () use ($batch, $row): void {
            $lockedBatch = BulkExcelImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            $lockedRow = BulkExcelImportRow::query()->lockForUpdate()->findOrFail($row->getKey());

            if ($lockedRow->batch_id !== $lockedBatch->id || ! $lockedRow->is_selected) {
                throw ValidationException::withMessages(['retry' => 'Data ini tidak dapat diproses kembali.']);
            }

            $retryableRow = $lockedRow->status === BulkExcelImportRow::STATUS_READY
                || ($lockedRow->status === BulkExcelImportRow::STATUS_FAILED && $lockedRow->failure_code === 'transient');
            if (! in_array($lockedBatch->status, [BulkExcelImportBatch::STATUS_PARTIAL, BulkExcelImportBatch::STATUS_FAILED], true)
                || ! $retryableRow) {
                throw ValidationException::withMessages(['retry' => 'Perbaiki data terlebih dahulu atau muat ulang hasil terbaru.']);
            }

            $lockedRow->update([
                'status' => BulkExcelImportRow::STATUS_QUEUED,
                'attempts' => 0,
                'last_error' => null,
                'failure_code' => null,
                'conflicting_pembanding_id' => null,
            ]);
            $lockedBatch->update(['status' => BulkExcelImportBatch::STATUS_PROCESSING, 'last_activity_at' => now()]);
        });

        try {
            ProcessBulkExcelImportChunk::dispatch($batch->id, [$row->id])->afterCommit();
        } catch (Throwable $exception) {
            report($exception);
            $row->refresh()->update([
                'status' => BulkExcelImportRow::STATUS_FAILED,
                'last_error' => 'Proses belum dapat dijadwalkan. Silakan coba kembali.',
                'failure_code' => 'transient',
            ]);
            app(RefreshBulkExcelImportBatchSummaryAction::class)->execute($batch->refresh());

            throw ValidationException::withMessages(['retry' => 'Proses belum dapat dijadwalkan. Coba kembali beberapa saat lagi.']);
        }
    }
}
