<?php

namespace App\Actions\BulkExcelImport;

use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BulkApplyBulkExcelImportRowsAction
{
    public const ALLOWED_FIELDS = [
        'status_pemberi_informasi_id',
        'bentuk_tanah_id',
        'posisi_tanah_id',
        'kondisi_tanah_id',
        'topografi_id',
        'dokumen_tanah_id',
        'peruntukan_id',
    ];

    public function __construct(
        private readonly UpdateBulkExcelImportRowAction $updateRow,
        private readonly RefreshBulkExcelImportBatchSummaryAction $refreshSummary,
    ) {}

    public function execute(BulkExcelImportBatch $batch, string $field, int $value): int
    {
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new InvalidArgumentException('Field tidak diizinkan untuk diterapkan ke banyak data.');
        }

        return DB::transaction(function () use ($batch, $field, $value): int {
            $lockedBatch = BulkExcelImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            if ($lockedBatch->status !== BulkExcelImportBatch::STATUS_DRAFT) {
                throw ValidationException::withMessages(['field' => 'Perubahan massal tidak dapat dilakukan setelah proses dimulai.']);
            }

            $rows = $lockedBatch->rows()
                ->reorder('id')
                ->where('is_selected', true)
                ->whereNull('duplicate_of_row_id')
                ->whereNull('pembanding_id')
                ->whereNotIn('status', [
                    BulkExcelImportRow::STATUS_DUPLICATE,
                    BulkExcelImportRow::STATUS_IMPORTED,
                ])
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $this->updateRow->execute($row, [$field => $value], refreshBatchSummary: false);
            }

            $this->refreshSummary->execute($lockedBatch);

            return $rows->count();
        });
    }
}
