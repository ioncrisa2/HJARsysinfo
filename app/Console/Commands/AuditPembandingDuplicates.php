<?php

namespace App\Console\Commands;

use App\Models\Pembanding;
use App\Services\Pembanding\PembandingFingerprintService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuditPembandingDuplicates extends Command
{
    protected $signature = 'pembanding:audit-duplicates
        {--write : Persist checksums and fingerprints}
        {--report= : Report path relative to storage/app}';

    protected $description = 'Audit exact duplicate pembanding records and optionally backfill fingerprints';

    public function handle(PembandingFingerprintService $fingerprints): int
    {
        $write = (bool) $this->option('write');
        $groups = [];
        $missingImages = [];
        $processed = 0;

        Pembanding::withTrashed()
            ->orderBy('id')
            ->chunkById(200, function ($records) use (
                $fingerprints,
                $write,
                &$groups,
                &$missingImages,
                &$processed
            ): void {
                foreach ($records as $record) {
                    $missingImage = $record->image
                        && ! Storage::disk('public')->exists($record->image);
                    $checksum = $fingerprints->checksumStoredImage($record->image);
                    $fingerprint = $fingerprints->fingerprint($record, $checksum);

                    if ($missingImage) {
                        $missingImages[] = $record->getKey();
                    }

                    $groups[$fingerprint][] = [
                        'id' => $record->getKey(),
                        'active' => ! $record->trashed(),
                        'created_by' => $record->created_by,
                        'created_at' => $record->created_at?->toDateTimeString(),
                        'missing_image' => (bool) $missingImage,
                    ];

                    if ($write) {
                        DB::table('data_pembanding')
                            ->where('id', $record->getKey())
                            ->update([
                                'image_checksum' => $checksum,
                                'business_fingerprint' => $fingerprint,
                                'active_fingerprint' => null,
                            ]);
                    }

                    $processed++;
                }
            });

        $duplicateGroups = array_filter($groups, fn (array $records): bool => count($records) > 1);

        if ($write) {
            $this->activateNonConflictingFingerprints($groups);
        }

        $reportPath = $this->writeReport($duplicateGroups, $missingImages);

        $this->info("Processed: {$processed}");
        $this->line('Mode: '.($write ? 'write' : 'dry-run'));
        $this->line('Duplicate groups: '.count($duplicateGroups));
        $this->line('Missing images: '.count($missingImages));
        $this->line('Report: '.Storage::disk('local')->path($reportPath));

        if (! $write) {
            $this->warn('No database rows were changed. Re-run with --write after reviewing the report.');
        }

        return self::SUCCESS;
    }

    private function activateNonConflictingFingerprints(array $groups): void
    {
        foreach ($groups as $fingerprint => $records) {
            $activeIds = collect($records)->where('active', true)->pluck('id');

            if ($activeIds->count() !== 1) {
                continue;
            }

            DB::table('data_pembanding')
                ->where('id', $activeIds->first())
                ->update(['active_fingerprint' => $fingerprint]);
        }
    }

    private function writeReport(array $duplicateGroups, array $missingImages): string
    {
        $path = $this->option('report')
            ?: 'reports/pembanding_duplicate_audit_'.now()->format('Ymd_His').'.csv';
        $stream = fopen('php://temp', 'w+');

        fputcsv($stream, [
            'type', 'fingerprint', 'record_ids', 'active_ids', 'created_by',
            'created_at', 'missing_image_ids',
        ]);

        foreach ($duplicateGroups as $fingerprint => $records) {
            $collection = collect($records);
            fputcsv($stream, [
                'duplicate_group',
                $fingerprint,
                $collection->pluck('id')->implode('|'),
                $collection->where('active', true)->pluck('id')->implode('|'),
                $collection->pluck('created_by')->implode('|'),
                $collection->pluck('created_at')->implode('|'),
                $collection->where('missing_image', true)->pluck('id')->implode('|'),
            ]);
        }

        foreach ($missingImages as $id) {
            fputcsv($stream, ['missing_image', '', $id, '', '', '', $id]);
        }

        rewind($stream);
        Storage::disk('local')->put($path, stream_get_contents($stream));
        fclose($stream);

        return $path;
    }
}
