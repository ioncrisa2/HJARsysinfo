<?php

namespace App\Filament\Pages;

use App\Services\Backup\SystemBackupService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SystemBackup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Backup Sistem';
    protected static ?string $title = 'Backup Sistem';

    protected static ?string $slug = 'system-backup';

    protected static string $view = 'filament.pages.system-backup';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backup_database')
                ->label('Backup Database')
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Buat Backup Database')
                ->modalDescription('Sistem akan membuat file SQL dari database aktif.')
                ->action(fn () => $this->backupDatabase()),

            Actions\Action::make('backup_uploads')
                ->label('Backup Uploaded Files')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Buat Backup Uploaded Files')
                ->modalDescription('Sistem akan membuat file ZIP dari storage/app/public.')
                ->action(fn () => $this->backupUploads()),
        ];
    }

    public function backupDatabase(): ?BinaryFileResponse
    {
        try {
            $path = app(SystemBackupService::class)->createDatabaseBackup();

            Notification::make()
                ->title('Backup database berhasil dibuat')
                ->body(basename($path))
                ->success()
                ->send();

            return response()->download($path, basename($path));
        } catch (Throwable $e) {
            Notification::make()
                ->title('Backup database gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function backupUploads(): ?BinaryFileResponse
    {
        try {
            $path = app(SystemBackupService::class)->createUploadedFilesBackup();

            Notification::make()
                ->title('Backup uploaded files berhasil dibuat')
                ->body(basename($path))
                ->success()
                ->send();

            return response()->download($path, basename($path));
        } catch (Throwable $e) {
            Notification::make()
                ->title('Backup uploaded files gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }
}
