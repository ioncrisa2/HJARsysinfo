<?php

namespace App\Actions\BulkExcelImport;

use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use Illuminate\Support\Facades\DB;

class RefreshBulkExcelImportBatchSummaryAction
{
    public function execute(BulkExcelImportBatch $batch): BulkExcelImportBatch
    {
        return DB::transaction(function () use ($batch): BulkExcelImportBatch {
            $locked = BulkExcelImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            $counts = $locked->rows()
                ->reorder()
                ->selectRaw('COUNT(*) as total_rows')
                ->selectRaw('SUM(CASE WHEN is_selected = 1 THEN 1 ELSE 0 END) as selected_rows')
                ->selectRaw('SUM(CASE WHEN is_selected = 1 AND status = ? THEN 1 ELSE 0 END) as ready_rows', [BulkExcelImportRow::STATUS_READY])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as imported_rows', [BulkExcelImportRow::STATUS_IMPORTED])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_rows', [BulkExcelImportRow::STATUS_FAILED])
                ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as rejected_rows', [
                    BulkExcelImportRow::STATUS_FINAL_DUPLICATE,
                    BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                ])
                ->selectRaw('SUM(CASE WHEN is_selected = 1 AND status IN (?, ?) THEN 1 ELSE 0 END) as processing_rows', [
                    BulkExcelImportRow::STATUS_QUEUED,
                    BulkExcelImportRow::STATUS_PROCESSING,
                ])
                ->first();

            $failedRows = (int) $counts->failed_rows + (int) $counts->rejected_rows;
            $status = $locked->status;
            $finalizedAt = $locked->finalized_at;
            if ($status !== BulkExcelImportBatch::STATUS_DRAFT) {
                if ((int) $counts->processing_rows > 0) {
                    $status = BulkExcelImportBatch::STATUS_PROCESSING;
                    $finalizedAt = null;
                } elseif ((int) $counts->imported_rows === (int) $counts->selected_rows) {
                    $status = BulkExcelImportBatch::STATUS_COMPLETE;
                    $finalizedAt ??= now();
                } elseif ((int) $counts->imported_rows > 0) {
                    $status = BulkExcelImportBatch::STATUS_PARTIAL;
                    $finalizedAt ??= now();
                } else {
                    $status = BulkExcelImportBatch::STATUS_FAILED;
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
