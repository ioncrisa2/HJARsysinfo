<?php

namespace App\Enums;

enum BackupType: string
{
    case Database = 'database';
    case Uploads = 'uploads';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Uploads => 'Uploaded files',
            self::Full => 'Backup lengkap',
        };
    }

    public function includesDatabase(): bool
    {
        return $this !== self::Uploads;
    }

    public function includesUploads(): bool
    {
        return $this !== self::Database;
    }
}
