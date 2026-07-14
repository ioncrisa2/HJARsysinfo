<?php

namespace App\Console\Commands;

use App\Models\ExportRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredExports extends Command
{
    protected $signature = 'exports:cleanup';

    protected $description = 'Menghapus file export yang sudah kedaluwarsa dari storage private';

    public function handle(): int
    {
        ExportRun::query()
            ->where('status', ExportRun::STATUS_PROCESSING)
            ->where('started_at', '<=', now()->subHours(2))
            ->each(function (ExportRun $run): void {
                $run->update([
                    'status' => ExportRun::STATUS_FAILED,
                    'failed_at' => now(),
                    'error' => 'Proses melewati batas dua jam dan ditandai gagal oleh audit terjadwal.',
                ]);
                activity('export')->performedOn($run)->event('stalled')->log('Job export terhenti ditandai gagal');
            });

        ExportRun::query()
            ->where('status', ExportRun::STATUS_COMPLETED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($runs): void {
                foreach ($runs as $run) {
                    if ($run->path) {
                        Storage::disk($run->disk)->delete($run->path);
                    }
                    $run->update(['status' => ExportRun::STATUS_EXPIRED, 'path' => null]);
                    activity('export')->performedOn($run)->event('expired')->log('File export kedaluwarsa dan dihapus');
                }
            });

        return self::SUCCESS;
    }
}
