<?php

namespace App\Actions\P2pk;

use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Support\Facades\DB;

class RefreshP2pkImportBatchSummaryAction
{
    public function execute(P2pkImportBatch $batch): P2pkImportBatch
    {
        return DB::transaction(function () use ($batch): P2pkImportBatch {
            $locked = P2pkImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            $counts = $locked->rows()
                ->reorder()
                ->selectRaw('COUNT(*) as total_rows')
                ->selectRaw('SUM(CASE WHEN is_selected = 1 THEN 1 ELSE 0 END) as selected_rows')
                ->selectRaw('SUM(CASE WHEN is_selected = 1 AND status = ? THEN 1 ELSE 0 END) as ready_rows', [P2pkImportRow::STATUS_READY])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as imported_rows', [P2pkImportRow::STATUS_IMPORTED])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_rows', [P2pkImportRow::STATUS_FAILED])
                ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as rejected_rows', [
                    P2pkImportRow::STATUS_FINAL_DUPLICATE,
                    P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                ])
                ->selectRaw('SUM(CASE WHEN is_selected = 1 AND status IN (?, ?) THEN 1 ELSE 0 END) as processing_rows', [
                    P2pkImportRow::STATUS_QUEUED,
                    P2pkImportRow::STATUS_PROCESSING,
                ])
                ->first();

            $failedRows = (int) $counts->failed_rows + (int) $counts->rejected_rows;
            $status = $locked->status;
            $finalizedAt = $locked->finalized_at;
            if ($status !== P2pkImportBatch::STATUS_DRAFT) {
                if ((int) $counts->processing_rows > 0) {
                    $status = P2pkImportBatch::STATUS_PROCESSING;
                    $finalizedAt = null;
                } elseif ((int) $counts->imported_rows === (int) $counts->selected_rows) {
                    $status = P2pkImportBatch::STATUS_COMPLETE;
                    $finalizedAt ??= now();
                } elseif ((int) $counts->imported_rows > 0) {
                    $status = P2pkImportBatch::STATUS_PARTIAL;
                    $finalizedAt ??= now();
                } else {
                    $status = P2pkImportBatch::STATUS_FAILED;
                    $finalizedAt ??= now();
                }
            }

            $locked->update([
                'total_rows' => (int) $counts->total_rows,
                'selected_rows' => (int) $counts->selected_rows,
                'ready_rows' => (int) $counts->ready_rows,
                'imported_rows' => (int) $counts->imported_rows,
                'failed_rows' => $failedRows,
                'status' => $status,
                'finalized_at' => $finalizedAt,
                'last_activity_at' => now(),
            ]);

            return $locked->refresh();
        });
    }
}
