<?php

namespace App\Actions\P2pk;

use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BulkApplyP2pkImportRowsAction
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
        private readonly UpdateP2pkImportRowAction $updateRow,
        private readonly RefreshP2pkImportBatchSummaryAction $refreshSummary,
    ) {}

    public function execute(P2pkImportBatch $batch, string $field, int $value): int
    {
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new InvalidArgumentException('Field tidak diizinkan untuk diterapkan ke banyak data.');
        }

        return DB::transaction(function () use ($batch, $field, $value): int {
            $lockedBatch = P2pkImportBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            if ($lockedBatch->status !== P2pkImportBatch::STATUS_DRAFT) {
                throw ValidationException::withMessages(['field' => 'Perubahan massal tidak dapat dilakukan setelah proses dimulai.']);
            }

            $rows = $lockedBatch->rows()
                ->reorder('id')
                ->where('is_selected', true)
                ->whereNull('duplicate_of_row_id')
                ->whereNull('pembanding_id')
                ->whereNotIn('status', [
                    P2pkImportRow::STATUS_DUPLICATE,
                    P2pkImportRow::STATUS_IMPORTED,
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
