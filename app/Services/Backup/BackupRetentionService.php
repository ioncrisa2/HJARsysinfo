<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;

final class BackupRetentionService
{
    public function __construct(private readonly BackupCatalogService $catalog) {}

    public function prune(): int
    {
        $cutoff = now()->subDays((int) config('system_backup.retention_days', 30));
        $deleted = 0;

        foreach ($this->catalog->all() as $artifact) {
            if ($artifact->origin !== 'generated'
                || CarbonImmutable::parse($artifact->createdAt)->greaterThanOrEqualTo($cutoff)) {
                continue;
            }

            $this->catalog->delete($artifact);
            $deleted++;
        }

        return $deleted;
    }
}
