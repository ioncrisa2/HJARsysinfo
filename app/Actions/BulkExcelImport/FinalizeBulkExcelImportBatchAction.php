<?php

namespace App\Actions\BulkExcelImport;

use App\Jobs\ProcessBulkExcelImportChunk;
use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinalizeBulkExcelImportBatchAction
{
    public function execute(BulkExcelImportBatch $batch, User $initiator): BulkExcelImportBatch
    {
        $chunks = DB::transaction(function () use ($batch, $initiator): array {
            $locked = BulkExcelImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());

            if ($locked->status !== BulkExcelImportBatch::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'finalize' => 'Data ini sudah pernah mulai diproses. Muat ulang halaman untuk melihat hasil terbaru.',
                ]);
            }

            $selected = $locked->rows()->reorder()->where('is_selected', true)->lockForUpdate()->get();
            if ($selected->isEmpty()) {
                throw ValidationException::withMessages(['finalize' => 'Pilih setidaknya satu data untuk dimasukkan.']);
            }

            $notReady = $selected->where('status', '!=', BulkExcelImportRow::STATUS_READY)->count();
            if ($notReady > 0) {
                throw ValidationException::withMessages([
                    'finalize' => "Masih ada {$notReady} data terpilih yang belum lengkap.",
                ]);
            }

            $rowIds = $selected->pluck('id');
            BulkExcelImportRow::query()->whereKey($rowIds)->update([
                'status' => BulkExcelImportRow::STATUS_QUEUED,
                'attempts' => 0,
                'last_error' => null,
                'failure_code' => null,
            ]);

            $locked->update([
                'status' => BulkExcelImportBatch::STATUS_PROCESSING,
                'finalization_date' => now('Asia/Jakarta')->toDateString(),
                'initiated_by' => $initiator->getKey(),
                'finalized_at' => null,
                'last_activity_at' => now(),
            ]);

            return $rowIds->chunk(10)->map->values()->map->all()->all();
        });

        foreach ($chunks as $index => $rowIds) {
            try {
                ProcessBulkExcelImportChunk::dispatch($batch->getKey(), $rowIds)->afterCommit();
            } catch (Throwable $exception) {
                report($exception);
                $undispatchedIds = collect(array_slice($chunks, $index))->flatten()->all();
                BulkExcelImportRow::query()->whereKey($undispatchedIds)->where('status', BulkExcelImportRow::STATUS_QUEUED)->update([
                    'status' => BulkExcelImportRow::STATUS_FAILED,
                    'last_error' => 'Proses belum dapat dijadwalkan. Silakan coba proses kembali.',
                    'failure_code' => 'transient',
                ]);
                app(RefreshBulkExcelImportBatchSummaryAction::class)->execute($batch->refresh());
                break;
            }
        }

        return $batch->refresh();
    }
}
