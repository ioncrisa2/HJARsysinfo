<?php

namespace App\Actions\P2pk;

use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use App\Services\P2pk\P2pkDraftReadinessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class UpdateP2pkImportRowAction
{
    public function __construct(
        private readonly P2pkDraftReadinessService $readiness,
        private readonly RefreshP2pkImportBatchSummaryAction $refreshSummary,
    ) {}

    /** @param array<string, mixed> $changes */
    public function execute(
        P2pkImportRow $row,
        array $changes,
        ?UploadedFile $image = null,
        bool $refreshBatchSummary = true,
    ): P2pkImportRow {
        if ($row->pembanding_id !== null || $row->status === P2pkImportRow::STATUS_IMPORTED) {
            throw new LogicException('Data yang sudah diimpor tidak dapat diubah.');
        }

        $removeImage = (bool) ($changes['remove_image'] ?? false) && $image === null;
        unset($changes['image'], $changes['remove_image'], $changes['tanggal_data']);

        $newImage = $image ? $this->storeImage($row, $image) : null;
        $oldImage = null;

        try {
            $updated = DB::transaction(function () use ($row, $changes, $image, $newImage, $removeImage, $refreshBatchSummary, &$oldImage): P2pkImportRow {
                $lockedBatch = P2pkImportBatch::query()->lockForUpdate()->findOrFail($row->batch_id);
                if (! $lockedBatch->allowsDraftChanges()) {
                    throw new LogicException('Data tidak dapat diubah selama proses memasukkan data berjalan.');
                }

                /** @var P2pkImportRow $locked */
                $locked = P2pkImportRow::query()->lockForUpdate()->findOrFail($row->getKey());
                if ($locked->pembanding_id !== null || $locked->status === P2pkImportRow::STATUS_IMPORTED) {
                    throw new LogicException('Data yang sudah diimpor tidak dapat diubah.');
                }

                $payload = array_replace($locked->mapped_payload ?? [], $changes);
                $imageAttributes = [];
                if ($image && $newImage) {
                    $oldImage = $locked->staging_image_path ? [
                        'disk' => $locked->staging_image_disk ?: 'local',
                        'path' => $locked->staging_image_path,
                    ] : null;
                    $imageAttributes = [
                        'staging_image_disk' => 'local',
                        'staging_image_path' => $newImage,
                        'staging_image_original_name' => mb_substr(basename($image->getClientOriginalName()), 0, 255),
                        'staging_image_mime' => mb_substr((string) $image->getMimeType(), 0, 100),
                        'staging_image_size' => $image->getSize(),
                    ];
                } elseif ($removeImage && $locked->staging_image_path) {
                    $oldImage = [
                        'disk' => $locked->staging_image_disk ?: 'local',
                        'path' => $locked->staging_image_path,
                    ];
                    $imageAttributes = [
                        'staging_image_disk' => null,
                        'staging_image_path' => null,
                        'staging_image_original_name' => null,
                        'staging_image_mime' => null,
                        'staging_image_size' => null,
                    ];
                }

                $warnings = $this->remainingMappingWarnings($locked->warnings ?? [], $payload, $changes);
                $readiness = $this->readiness->evaluate(
                    $payload,
                    $newImage !== null || (! $removeImage && $locked->staging_image_path !== null),
                );
                $validationWarnings = array_map(
                    fn (array $error): array => [...$error, 'type' => 'validation'],
                    $readiness['validation_errors'],
                );

                $status = match (true) {
                    $locked->duplicate_of_row_id !== null => P2pkImportRow::STATUS_DUPLICATE,
                    $warnings !== [] => P2pkImportRow::STATUS_NEEDS_CONFIRMATION,
                    $validationWarnings !== [] => P2pkImportRow::STATUS_INVALID,
                    $readiness['missing_fields'] !== [] => P2pkImportRow::STATUS_INCOMPLETE,
                    default => P2pkImportRow::STATUS_READY,
                };

                $locked->update([
                    'mapped_payload' => $payload,
                    'missing_fields' => $readiness['missing_fields'],
                    'warnings' => [...$warnings, ...$validationWarnings],
                    'status' => $status,
                    'last_error' => null,
                    'failure_code' => null,
                    'conflicting_pembanding_id' => null,
                    'attempts' => 0,
                    ...$imageAttributes,
                ]);

                if ($refreshBatchSummary) {
                    $this->refreshSummary->execute($lockedBatch);
                }

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('local')->delete($newImage);
            }

            throw $exception;
        }

        if ($oldImage) {
            Storage::disk($oldImage['disk'])->delete($oldImage['path']);
        }

        return $updated->refresh();
    }

    private function storeImage(P2pkImportRow $row, UploadedFile $image): string
    {
        $extension = strtolower($image->guessExtension() ?: $image->getClientOriginalExtension());
        $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $directory = 'p2pk-imports/'.$row->batch->owner_id.'/images/'.$row->getKey();
        $path = Storage::disk('local')->putFileAs($directory, $image, $filename);

        if ($path === false) {
            throw new \RuntimeException('Gambar draft gagal disimpan. Coba unggah kembali.');
        }

        return $path;
    }

    /**
     * @param  array<int, array{field: string, message: string}>  $warnings
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $changes
     * @return array<int, array{field: string, message: string}>
     */
    private function remainingMappingWarnings(array $warnings, array $payload, array $changes): array
    {
        return collect($warnings)
            ->reject(fn (array $warning): bool => ($warning['type'] ?? null) === 'validation')
            ->reject(function (array $warning) use ($payload, $changes): bool {
                $field = $warning['field'];
                if ($field === 'coordinates') {
                    return (array_key_exists('latitude', $changes) || array_key_exists('longitude', $changes))
                        && filled($payload['latitude'] ?? null)
                        && filled($payload['longitude'] ?? null);
                }

                return array_key_exists($field, $changes) && filled($payload[$field] ?? null);
            })
            ->values()
            ->all();
    }
}
