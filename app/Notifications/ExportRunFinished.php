<?php

namespace App\Notifications;

use App\Models\ExportRun;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExportRunFinished extends Notification
{
    use Queueable;

    public function __construct(private readonly ExportRun $exportRun) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'export_run_id' => $this->exportRun->id,
            'status' => $this->exportRun->status,
            'message' => $this->exportRun->status === ExportRun::STATUS_COMPLETED
                ? 'Export data selesai dan siap diunduh.'
                : 'Export data gagal diproses.',
        ];
    }
}
