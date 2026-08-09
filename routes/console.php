<?php

use App\Services\Backup\BackupRetentionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune')->daily();
Schedule::command('exports:cleanup')->dailyAt('02:30')->withoutOverlapping();

Artisan::command('backups:prune', function (BackupRetentionService $retention): void {
    $this->info($retention->prune().' backup melewati retensi telah dihapus.');
})->purpose('Delete generated system backups that exceeded retention');

Schedule::command('backups:prune')->dailyAt('03:00')->withoutOverlapping();
