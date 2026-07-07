<?php

namespace App\Actions\P2pk;

use App\Jobs\ProcessP2pkImportChunk;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinalizeP2pkImportBatchAction
{
    public function execute(P2pkImportBatch $batch, User $initiator): P2pkImportBatch
    {
        $chunks = DB::transaction(function () use ($batch, $initiator): array {
            $locked = P2pkImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());

            if ($locked->status !== P2pkImportBatch::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'finalize' => 'Data ini sudah pernah mulai diproses. Muat ulang halaman untuk melihat hasil terbaru.',
                ]);
            }

            $selected = $locked->rows()->reorder()->where('is_selected', true)->lockForUpdate()->get();
            if ($selected->isEmpty()) {
                throw ValidationException::withMessages(['finalize' => 'Pilih setidaknya satu data untuk dimasukkan.']);
            }

            $notReady = $selected->where('status', '!=', P2pkImportRow::STATUS_READY)->count();
            if ($notReady > 0) {
                throw ValidationException::withMessages([
                    'finalize' => "Masih ada {$notReady} data terpilih yang belum lengkap.",
                ]);
            }

            $rowIds = $selected->pluck('id');
            P2pkImportRow::query()->whereKey($rowIds)->update([
                'status' => P2pkImportRow::STATUS_QUEUED,
                'attempts' => 0,
                'last_error' => null,
                'failure_code' => null,
            ]);

            $locked->update([
                'status' => P2pkImportBatch::STATUS_PROCESSING,
                'finalization_date' => now('Asia/Jakarta')->toDateString(),
                'initiated_by' => $initiator->getKey(),
                'finalized_at' => null,
                'last_activity_at' => now(),
            ]);

            return $rowIds->chunk(10)->map->values()->map->all()->all();
        });

        foreach ($chunks as $index => $rowIds) {
            try {
                ProcessP2pkImportChunk::dispatch($batch->getKey(), $rowIds)->afterCommit();
            } catch (Throwable $exception) {
                report($exception);
                $undispatchedIds = collect(array_slice($chunks, $index))->flatten()->all();
                P2pkImportRow::query()->whereKey($undispatchedIds)->where('status', P2pkImportRow::STATUS_QUEUED)->update([
                    'status' => P2pkImportRow::STATUS_FAILED,
                    'last_error' => 'Proses belum dapat dijadwalkan. Silakan coba proses kembali.',
                    'failure_code' => 'transient',
                ]);
                app(RefreshP2pkImportBatchSummaryAction::class)->execute($batch->refresh());
                break;
            }
        }

        return $batch->refresh();
    }
}
