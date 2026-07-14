<?php

namespace App\Jobs;

use App\Models\ExportRun;
use App\Notifications\ExportRunFinished;
use App\Services\Exports\PembandingExportFileService;
use App\Services\Exports\PembandingExportQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GeneratePembandingExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public readonly int $exportRunId) {}

    public function handle(PembandingExportQueryService $queryService, PembandingExportFileService $fileService): void
    {
        $run = ExportRun::query()->with('user')->findOrFail($this->exportRunId);
        if ($run->status !== ExportRun::STATUS_PENDING) {
            return;
        }

        $run->update([
            'status' => ExportRun::STATUS_PROCESSING,
            'started_at' => now(),
            'error' => null,
        ]);

        $query = $queryService->query(
            $run->user,
            $run->filters ?? [],
            $run->scope === 'selected' ? ($run->selected_ids ?? []) : [],
            $run->snapshot_at,
        );
        $count = (clone $query)->count();

        $metadata = [
            'Dibuat pada' => now()->format('Y-m-d H:i:s T'),
            'Diminta oleh' => $run->user->name,
            'Format' => strtoupper($run->format),
            'Mode' => $run->mode,
            'Profil' => $run->profile,
            'Scope' => $run->scope,
            'Jumlah data' => $count,
            'Filter' => array_filter($run->filters ?? [], fn (mixed $value): bool => filled($value)),
        ];

        $file = $fileService->storeQuery(
            $query,
            $run->format,
            $run->mode ?? 'summary',
            $run->columns ?? [],
            $metadata,
            $run->disk,
            "exports/{$run->user_id}/{$run->id}",
        );

        $run->update([
            ...$file,
            'status' => ExportRun::STATUS_COMPLETED,
            'processed_records' => $count,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        activity('export')->causedBy($run->user)->performedOn($run)->event('completed')
            ->withProperties(['records' => $count, 'checksum' => $file['checksum']])->log('Export selesai diproses');
        $run->user->notify(new ExportRunFinished($run));
    }

    public function failed(Throwable $exception): void
    {
        $run = ExportRun::query()->with('user')->find($this->exportRunId);
        if (! $run) {
            return;
        }

        $run->update([
            'status' => ExportRun::STATUS_FAILED,
            'failed_at' => now(),
            'error' => str($exception->getMessage())->limit(2000)->toString(),
        ]);
        activity('export')->causedBy($run->user)->performedOn($run)->event('failed')->log('Export gagal diproses');
        $run->user?->notify(new ExportRunFinished($run));
    }
}
