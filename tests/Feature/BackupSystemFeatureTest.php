<?php

use App\Enums\BackupType;
use App\Exceptions\IncompleteUploadsRollbackException;
use App\Models\User;
use App\Services\Backup\BackupCatalogService;
use App\Services\Backup\BackupDirectoryOperator;
use App\Services\Backup\BackupPackageInspector;
use App\Services\Backup\SafeZipExtractor;
use App\Services\Backup\SystemBackupService;
use App\Services\Backup\SystemRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->backupTestId = strtolower((string) Str::ulid());
    $this->backupRoot = storage_path("framework/testing/backups-{$this->backupTestId}");
    $this->uploadRootName = "backup-test-{$this->backupTestId}";
    $this->uploadRoot = storage_path("app/public/{$this->uploadRootName}");
    config()->set('system_backup.root', $this->backupRoot);
    config()->set('system_backup.signing_key', 'test-signing-key');
    config()->set('system_backup.upload_roots', [$this->uploadRootName]);
    config()->set('system_backup.allowed_upload_extensions', ['jpg', 'png']);
    File::ensureDirectoryExists($this->uploadRoot);
});

afterEach(function () {
    File::deleteDirectory($this->backupRoot);
    File::deleteDirectory($this->uploadRoot);
});

it('creates a signed uploads package with manifest checksum and private catalog', function () {
    File::put("{$this->uploadRoot}/house.jpg", 'image-content');
    $user = User::factory()->create();

    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);
    $manifest = app(BackupPackageInspector::class)->inspect($artifact->path);

    expect($artifact->verified)->toBeTrue()
        ->and($artifact->type)->toBe(BackupType::Uploads)
        ->and(File::exists($artifact->path))->toBeTrue()
        ->and(hash_file('sha256', $artifact->path))->toBe($artifact->checksum)
        ->and($manifest['payloads'][0]['name'])->toBe('payload/uploads.zip')
        ->and(app(BackupCatalogService::class)->all())->toHaveCount(1);
});

it('imports only a signed system backup package', function () {
    File::put("{$this->uploadRoot}/house.jpg", 'image-content');
    $user = User::factory()->create();
    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);
    $importRoot = storage_path("framework/testing/imports-{$this->backupTestId}");
    config()->set('system_backup.root', $importRoot);

    try {
        $upload = new UploadedFile($artifact->path, 'restore-point.sbackup', 'application/zip', null, true);
        $imported = app(SystemBackupService::class)->import($upload, $user);

        expect($imported->origin)->toBe('imported')
            ->and($imported->id)->toBe($artifact->id)
            ->and(File::exists($imported->path))->toBeTrue();
    } finally {
        File::deleteDirectory($importRoot);
    }
});

it('rejects zip traversal before writing outside staging', function () {
    $archive = "{$this->backupRoot}/malicious.zip";
    File::ensureDirectoryExists(dirname($archive));
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('../escape.jpg', 'malicious');
    $zip->close();
    $target = "{$this->backupRoot}/staging";

    expect(fn () => app(SafeZipExtractor::class)->extract($archive, $target))
        ->toThrow(RuntimeException::class);
    expect(File::exists("{$this->backupRoot}/escape.jpg"))->toBeFalse();
});

it('refuses to publish an uploads backup containing a disallowed file type', function () {
    File::put("{$this->uploadRoot}/report.pdf", 'pdf-content');

    expect(fn () => app(SystemBackupService::class)->create(
        BackupType::Uploads,
        User::factory()->create(),
    ))->toThrow(RuntimeException::class, 'tipe file yang tidak diizinkan');

    expect(app(BackupCatalogService::class)->all())->toBeEmpty()
        ->and(File::glob("{$this->backupRoot}/artifacts/*"))->toBeEmpty();
});

it('refuses to publish an uploads backup containing a hidden path', function () {
    File::ensureDirectoryExists("{$this->uploadRoot}/.hidden");
    File::put("{$this->uploadRoot}/.hidden/image.jpg", 'image-content');

    expect(fn () => app(SystemBackupService::class)->create(
        BackupType::Uploads,
        User::factory()->create(),
    ))->toThrow(RuntimeException::class, 'path yang tidak diizinkan');

    expect(app(BackupCatalogService::class)->all())->toBeEmpty();
});

it('restores uploaded files through staging and creates a safety backup', function () {
    config()->set('system_backup.restore_enabled', true);
    File::put("{$this->uploadRoot}/house.jpg", 'backup-version');
    $user = User::factory()->create();
    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);
    File::put("{$this->uploadRoot}/house.jpg", 'live-version');

    app(SystemRestoreService::class)->restoreUploads($artifact, $user);

    expect(File::get("{$this->uploadRoot}/house.jpg"))->toBe('backup-version')
        ->and(app(BackupCatalogService::class)->all())->toHaveCount(2)
        ->and(app()->isDownForMaintenance())->toBeFalse();
});

it('preserves recovery data and audits its path when filesystem rollback fails', function () {
    config()->set('system_backup.restore_enabled', true);
    $secondRootName = "{$this->uploadRootName}-settings";
    $secondRoot = storage_path("app/public/{$secondRootName}");
    config()->set('system_backup.upload_roots', [$this->uploadRootName, $secondRootName]);
    File::ensureDirectoryExists($secondRoot);
    File::put("{$this->uploadRoot}/house.jpg", 'backup-first');
    File::put("{$secondRoot}/logo.png", 'backup-second');
    $user = User::factory()->create();
    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);
    File::put("{$this->uploadRoot}/house.jpg", 'live-first');
    File::put("{$secondRoot}/logo.png", 'live-second');

    $operator = new class($secondRootName) extends BackupDirectoryOperator
    {
        public function __construct(private readonly string $failingRoot) {}

        public function move(string $from, string $to): bool
        {
            $from = str_replace('\\', '/', $from);
            $to = str_replace('\\', '/', $to);
            $isActivationOrRollback = str_ends_with($to, '/'.$this->failingRoot)
                && (str_contains($from, '/staging/') || str_contains($from, '/rollback/'));

            return $isActivationOrRollback ? false : parent::move($from, $to);
        }
    };
    $this->app->instance(BackupDirectoryOperator::class, $operator);

    try {
        app(SystemRestoreService::class)->restoreUploads($artifact, $user);
        $this->fail('Restore seharusnya gagal saat rollback kedua gagal.');
    } catch (IncompleteUploadsRollbackException $exception) {
        $auditLines = array_filter(explode(
            PHP_EOL,
            File::get("{$this->backupRoot}/audit/operations.jsonl"),
        ));
        $lastAudit = json_decode(end($auditLines), true, flags: JSON_THROW_ON_ERROR);

        expect(File::isDirectory($exception->recoveryPath))->toBeTrue()
            ->and(File::get("{$exception->recoveryPath}/{$secondRootName}/logo.png"))
            ->toBe('live-second')
            ->and(File::get("{$this->uploadRoot}/house.jpg"))->toBe('live-first')
            ->and($lastAudit['recovery_path'])->toBe($exception->recoveryPath);
    } finally {
        File::deleteDirectory($secondRoot);
    }
});

it('rejects changed packages before touching live uploads', function () {
    config()->set('system_backup.restore_enabled', true);
    File::put("{$this->uploadRoot}/house.jpg", 'backup-version');
    $user = User::factory()->create();
    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);
    File::put("{$this->uploadRoot}/house.jpg", 'live-version');
    File::append($artifact->path, 'tampered');

    expect(fn () => app(SystemRestoreService::class)->restoreUploads($artifact, $user))
        ->toThrow(RuntimeException::class);
    expect(File::get("{$this->uploadRoot}/house.jpg"))->toBe('live-version');
});

it('rejects a generated package whose payload exceeds the configured bound', function () {
    config()->set('system_backup.max_package_megabytes', 1);
    File::put("{$this->uploadRoot}/large.jpg", random_bytes(2 * 1024 * 1024));

    expect(fn () => app(SystemBackupService::class)->create(
        BackupType::Uploads,
        User::factory()->create(),
    ))->toThrow(RuntimeException::class, 'melewati batas keamanan');

    expect(File::glob("{$this->backupRoot}/artifacts/*.partial"))->toBeEmpty()
        ->and(app(BackupCatalogService::class)->all())->toBeEmpty();
});

it('does not expose database host or absolute backup paths in api catalog', function () {
    Permission::findOrCreate('view_backup', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('view_backup');

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/backup/artifacts')->assertOk();
    $serialized = json_encode($response->json(), JSON_THROW_ON_ERROR);

    expect($response->json('status'))->toBe('success')
        ->and($serialized)->not->toContain((string) config('database.connections.mysql.host'))
        ->and($serialized)->not->toContain(storage_path());
});

it('downloads catalog artifacts only with the dedicated permission', function () {
    File::put("{$this->uploadRoot}/house.jpg", 'image-content');
    $user = User::factory()->create();
    foreach (['view_backup', 'download_backup'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo(['view_backup', 'download_backup']);
    $artifact = app(SystemBackupService::class)->create(BackupType::Uploads, $user);

    $response = $this->actingAs($user, 'sanctum')
        ->get("/api/v1/backup/artifacts/{$artifact->id}/download")
        ->assertOk();

    expect($response->headers->get('cache-control'))
        ->toContain('private')
        ->toContain('no-store');
});

it('returns not found for unknown backup downloads and deletes', function () {
    $user = User::factory()->create();
    foreach (['view_backup', 'download_backup', 'delete_backup'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo(['view_backup', 'download_backup', 'delete_backup']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/backup/artifacts/unknown-backup-id/download')
        ->assertNotFound();
    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/backup/artifacts/unknown-backup-id')
        ->assertNotFound();
});
