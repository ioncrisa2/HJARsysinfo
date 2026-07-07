<?php

namespace App\Actions\P2pk;

use App\Jobs\ProcessP2pkImportChunk;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RetryP2pkImportRowAction
{
    public function execute(P2pkImportBatch $batch, P2pkImportRow $row): void
    {
        DB::transaction(function () use ($batch, $row): void {
            $lockedBatch = P2pkImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            $lockedRow = P2pkImportRow::query()->lockForUpdate()->findOrFail($row->getKey());

            if ($lockedRow->batch_id !== $lockedBatch->id || ! $lockedRow->is_selected) {
                throw ValidationException::withMessages(['retry' => 'Data ini tidak dapat diproses kembali.']);
            }

            $retryableRow = $lockedRow->status === P2pkImportRow::STATUS_READY
                || ($lockedRow->status === P2pkImportRow::STATUS_FAILED && $lockedRow->failure_code === 'transient');
            if (! in_array($lockedBatch->status, [P2pkImportBatch::STATUS_PARTIAL, P2pkImportBatch::STATUS_FAILED], true)
                || ! $retryableRow) {
                throw ValidationException::withMessages(['retry' => 'Perbaiki data terlebih dahulu atau muat ulang hasil terbaru.']);
            }

            $lockedRow->update([
                'status' => P2pkImportRow::STATUS_QUEUED,
                'attempts' => 0,
                'last_error' => null,
                'failure_code' => null,
                'conflicting_pembanding_id' => null,
            ]);
            $lockedBatch->update(['status' => P2pkImportBatch::STATUS_PROCESSING, 'last_activity_at' => now()]);
        });

        try {
            ProcessP2pkImportChunk::dispatch($batch->id, [$row->id])->afterCommit();
        } catch (Throwable $exception) {
            report($exception);
            $row->refresh()->update([
                'status' => P2pkImportRow::STATUS_FAILED,
                'last_error' => 'Proses belum dapat dijadwalkan. Silakan coba kembali.',
                'failure_code' => 'transient',
            ]);
            app(RefreshP2pkImportBatchSummaryAction::class)->execute($batch->refresh());

            throw ValidationException::withMessages(['retry' => 'Proses belum dapat dijadwalkan. Coba kembali beberapa saat lagi.']);
        }
    }
}
