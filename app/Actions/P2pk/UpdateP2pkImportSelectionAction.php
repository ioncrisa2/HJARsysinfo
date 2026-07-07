<?php

namespace App\Actions\P2pk;

use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateP2pkImportSelectionAction
{
    public function __construct(private readonly RefreshP2pkImportBatchSummaryAction $refreshSummary) {}

    /** @param array<int, int> $rowIds */
    public function execute(P2pkImportBatch $batch, string $action, array $rowIds = [], bool $selected = false): void
    {
        DB::transaction(function () use ($batch, $action, $rowIds, $selected): void {
            $lockedBatch = P2pkImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            if ($lockedBatch->status !== P2pkImportBatch::STATUS_DRAFT) {
                throw ValidationException::withMessages(['selection' => 'Pilihan tidak dapat diubah setelah proses dimulai.']);
            }

            $query = $lockedBatch->rows()->whereNull('pembanding_id');

            if ($action === 'set_rows') {
                $rows = (clone $query)->whereKey($rowIds)->get(['id', 'status']);
                if ($rows->count() !== count(array_unique($rowIds))) {
                    throw ValidationException::withMessages(['selection' => 'Sebagian data tidak ditemukan pada unggahan ini.']);
                }

                if ($selected && $rows->contains('status', P2pkImportRow::STATUS_DUPLICATE)) {
                    throw ValidationException::withMessages(['selection' => 'Data yang sama tidak dapat dipilih. Periksa baris sumber terlebih dahulu.']);
                }

                (clone $query)->whereKey($rowIds)->update(['is_selected' => $selected]);
            } elseif ($action === 'select_all') {
                (clone $query)->where('status', P2pkImportRow::STATUS_DUPLICATE)->update(['is_selected' => false]);
                $query->where('status', '!=', P2pkImportRow::STATUS_DUPLICATE)->update(['is_selected' => true]);
            } elseif ($action === 'clear_all') {
                $query->update(['is_selected' => false]);
            } elseif ($action === 'select_ready') {
                $query->update(['is_selected' => false]);
                $lockedBatch->rows()->whereNull('pembanding_id')->where('status', P2pkImportRow::STATUS_READY)->update(['is_selected' => true]);
            }

            $lockedBatch->update(['last_activity_at' => now()]);
            $this->refreshSummary->execute($lockedBatch);
        });
    }
}
