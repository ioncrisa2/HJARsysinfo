<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminPermissions;
use App\Http\Controllers\Controller;
use App\Services\Backup\SystemBackupService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    use AuthorizesAdminPermissions;

    public function index(): Response
    {
        $this->authorizeAdmin('view_backup');

        $connectionName = (string) config('database.default');
        $connection = config("database.connections.{$connectionName}", []);

        return Inertia::render('Admin/Backup/Index', [
            'meta' => [
                'database' => [
                    'connection' => $connectionName,
                    'driver' => (string) ($connection['driver'] ?? '-'),
                    'database' => (string) ($connection['database'] ?? '-'),
                    'host' => (string) ($connection['host'] ?? '-'),
                ],
                'paths' => [
                    'database' => storage_path('app/backups/database'),
                    'uploads' => storage_path('app/backups/uploads'),
                    'source_uploads' => storage_path('app/public'),
                ],
                'requirements' => [
                    'zip' => class_exists(\ZipArchive::class),
                    'mysqldump' => (string) env('MYSQLDUMP_BINARY', 'mysqldump'),
                    'database_fallback' => 'PHP PDO',
                ],
            ],
            'history' => [
                'database' => $this->backupFiles(storage_path('app/backups/database')),
                'uploads' => $this->backupFiles(storage_path('app/backups/uploads')),
            ],
            'can' => AdminAccess::capabilityMap(request()->user(), [
                'database' => 'create_database_backup',
                'uploads' => 'create_uploads_backup',
            ]),
        ]);
    }

    public function database(SystemBackupService $backupService): BinaryFileResponse|JsonResponse
    {
        $this->authorizeAdmin('create_database_backup');

        try {
            $path = $backupService->createDatabaseBackup();

            return response()->download($path, basename($path));
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploads(SystemBackupService $backupService): BinaryFileResponse|JsonResponse
    {
        $this->authorizeAdmin('create_uploads_backup');

        try {
            $path = $backupService->createUploadedFilesBackup();

            return response()->download($path, basename($path));
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function backupFiles(string $directory): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
            ->take(10)
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'size_label' => $this->formatBytes($file->getSize()),
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024) {
                return number_format($value, 1)." {$unit}";
            }

            $value /= 1024;
        }

        return number_format($value, 1).' TB';
    }
}
