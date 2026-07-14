<?php

namespace App\Jobs;

use App\Actions\BulkExcelImport\ProcessBulkExcelImportRowAction;
use App\Actions\BulkExcelImport\RefreshBulkExcelImportBatchSummaryAction;
use App\Exceptions\BulkExcelImportRowProcessingException;
use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBulkExcelImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 75;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /** @param array<int, int> $rowIds */
    public function __construct(public readonly int $batchId, public readonly array $rowIds)
    {
        if ($rowIds === [] || count($rowIds) > 10) {
            throw new \InvalidArgumentException('Satu proses harus berisi 1 sampai 10 data.');
        }
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("bulk-excel-import-batch-{$this->batchId}"))->releaseAfter(5)->expireAfter(90)];
    }

    public function handle(
        ProcessBulkExcelImportRowAction $processRow,
        RefreshBulkExcelImportBatchSummaryAction $refreshSummary,
    ): void {
        $retryIds = [];

        foreach ($this->rowIds as $rowId) {
            $row = BulkExcelImportRow::query()->where('batch_id', $this->batchId)->find($rowId);
            if (! $row || $row->pembanding_id !== null) {
                if ($row) {
                    $processRow->execute($row);
                }

                continue;
            }

            if ($row->attempts >= 3) {
                $this->markFailed($row, 'Proses belum berhasil setelah tiga kali percobaan.', 'transient');

                continue;
            }

            $row->update([
                'status' => BulkExcelImportRow::STATUS_PROCESSING,
                'attempts' => $row->attempts + 1,
                'last_error' => null,
                'failure_code' => null,
            ]);

            try {
                $processRow->execute($row->refresh());
            } catch (BulkExcelImportRowProcessingException $exception) {
                $status = match ($exception->failureCode) {
                    'final_duplicate' => BulkExcelImportRow::STATUS_FINAL_DUPLICATE,
                    'source_already_imported' => BulkExcelImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                    default => BulkExcelImportRow::STATUS_FAILED,
                };

                $row->refresh()->update([
                    'status' => $status,
                    'last_error' => $exception->getMessage(),
                    'failure_code' => $exception->failureCode,
                    'conflicting_pembanding_id' => $exception->conflictingPembandingId,
                ]);

                if ($exception->retryable && $row->attempts < 3) {
                    $row->update(['status' => BulkExcelImportRow::STATUS_QUEUED]);
                    $retryIds[] = $row->id;
                }
            } catch (Throwable $exception) {
                report($exception);
                $row->refresh();
                if ($row->pembanding_id !== null) {
                    $row->update(['status' => BulkExcelImportRow::STATUS_IMPORTED, 'last_error' => null, 'failure_code' => null]);

                    continue;
                }

                if ($row->attempts < 3) {
                    $row->update([
                        'status' => BulkExcelImportRow::STATUS_QUEUED,
                        'last_error' => 'Sistem belum dapat memproses data ini. Percobaan akan diulangi.',
                        'failure_code' => 'transient',
                    ]);
                    $retryIds[] = $row->id;
                } else {
                    $this->markFailed($row, 'Proses belum berhasil setelah tiga kali percobaan.', 'transient');
                }
            }
        }

        $batch = BulkExcelImportBatch::query()->find($this->batchId);
        if ($batch) {
            $refreshSummary->execute($batch);
        }

        if ($retryIds !== []) {
            try {
                self::dispatch($this->batchId, $retryIds)->delay(now()->addSeconds(10));
            } catch (Throwable $exception) {
                report($exception);
                BulkExcelImportRow::query()->whereKey($retryIds)->update([
                    'status' => BulkExcelImportRow::STATUS_FAILED,
                    'last_error' => 'Percobaan ulang belum dapat dijadwalkan. Silakan coba proses kembali.',
                    'failure_code' => 'transient',
                ]);
                if ($batch) {
                    $refreshSummary->execute($batch->refresh());
                }
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        BulkExcelImportRow::query()
            ->where('batch_id', $this->batchId)
            ->whereKey($this->rowIds)
            ->whereIn('status', [BulkExcelImportRow::STATUS_QUEUED, BulkExcelImportRow::STATUS_PROCESSING])
            ->update([
                'status' => BulkExcelImportRow::STATUS_FAILED,
                'last_error' => 'Proses terhenti. Silakan coba proses kembali.',
                'failure_code' => 'transient',
            ]);

        $batch = BulkExcelImportBatch::query()->find($this->batchId);
        if ($batch) {
            app(RefreshBulkExcelImportBatchSummaryAction::class)->execute($batch);
        }
    }

    private function markFailed(BulkExcelImportRow $row, string $message, string $code): void
    {
        $row->update(['status' => BulkExcelImportRow::STATUS_FAILED, 'last_error' => $message, 'failure_code' => $code]);
    }
}
