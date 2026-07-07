<?php

namespace App\Jobs;

use App\Actions\P2pk\ProcessP2pkImportRowAction;
use App\Actions\P2pk\RefreshP2pkImportBatchSummaryAction;
use App\Exceptions\P2pkImportRowProcessingException;
use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessP2pkImportChunk implements ShouldQueue
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
        return [(new WithoutOverlapping("p2pk-import-batch-{$this->batchId}"))->releaseAfter(5)->expireAfter(90)];
    }

    public function handle(
        ProcessP2pkImportRowAction $processRow,
        RefreshP2pkImportBatchSummaryAction $refreshSummary,
    ): void {
        $retryIds = [];

        foreach ($this->rowIds as $rowId) {
            $row = P2pkImportRow::query()->where('batch_id', $this->batchId)->find($rowId);
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
                'status' => P2pkImportRow::STATUS_PROCESSING,
                'attempts' => $row->attempts + 1,
                'last_error' => null,
                'failure_code' => null,
            ]);

            try {
                $processRow->execute($row->refresh());
            } catch (P2pkImportRowProcessingException $exception) {
                $status = match ($exception->failureCode) {
                    'final_duplicate' => P2pkImportRow::STATUS_FINAL_DUPLICATE,
                    'source_already_imported' => P2pkImportRow::STATUS_SOURCE_ALREADY_IMPORTED,
                    default => P2pkImportRow::STATUS_FAILED,
                };

                $row->refresh()->update([
                    'status' => $status,
                    'last_error' => $exception->getMessage(),
                    'failure_code' => $exception->failureCode,
                    'conflicting_pembanding_id' => $exception->conflictingPembandingId,
                ]);

                if ($exception->retryable && $row->attempts < 3) {
                    $row->update(['status' => P2pkImportRow::STATUS_QUEUED]);
                    $retryIds[] = $row->id;
                }
            } catch (Throwable $exception) {
                report($exception);
                $row->refresh();
                if ($row->pembanding_id !== null) {
                    $row->update(['status' => P2pkImportRow::STATUS_IMPORTED, 'last_error' => null, 'failure_code' => null]);

                    continue;
                }

                if ($row->attempts < 3) {
                    $row->update([
                        'status' => P2pkImportRow::STATUS_QUEUED,
                        'last_error' => 'Sistem belum dapat memproses data ini. Percobaan akan diulangi.',
                        'failure_code' => 'transient',
                    ]);
                    $retryIds[] = $row->id;
                } else {
                    $this->markFailed($row, 'Proses belum berhasil setelah tiga kali percobaan.', 'transient');
                }
            }
        }

        $batch = P2pkImportBatch::query()->find($this->batchId);
        if ($batch) {
            $refreshSummary->execute($batch);
        }

        if ($retryIds !== []) {
            try {
                self::dispatch($this->batchId, $retryIds)->delay(now()->addSeconds(10));
            } catch (Throwable $exception) {
                report($exception);
                P2pkImportRow::query()->whereKey($retryIds)->update([
                    'status' => P2pkImportRow::STATUS_FAILED,
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
        P2pkImportRow::query()
            ->where('batch_id', $this->batchId)
            ->whereKey($this->rowIds)
            ->whereIn('status', [P2pkImportRow::STATUS_QUEUED, P2pkImportRow::STATUS_PROCESSING])
            ->update([
                'status' => P2pkImportRow::STATUS_FAILED,
                'last_error' => 'Proses terhenti. Silakan coba proses kembali.',
                'failure_code' => 'transient',
            ]);

        $batch = P2pkImportBatch::query()->find($this->batchId);
        if ($batch) {
            app(RefreshP2pkImportBatchSummaryAction::class)->execute($batch);
        }
    }

    private function markFailed(P2pkImportRow $row, string $message, string $code): void
    {
        $row->update(['status' => P2pkImportRow::STATUS_FAILED, 'last_error' => $message, 'failure_code' => $code]);
    }
}
