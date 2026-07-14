<?php

namespace App\Actions\BulkExcelImport;

use App\Actions\Pembanding\SavePembandingAction;
use App\Exceptions\DuplicatePembandingException;
use App\Exceptions\BulkExcelImportRowProcessingException;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Models\BulkExcelImportRow;
use App\Models\Pembanding;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessBulkExcelImportRowAction
{
    public function __construct(private readonly SavePembandingAction $savePembanding) {}

    public function execute(BulkExcelImportRow $row): BulkExcelImportRow
    {
        $created = null;

        try {
            $processed = DB::transaction(function () use ($row, &$created): BulkExcelImportRow {
                $locked = BulkExcelImportRow::query()->with('batch.initiatedBy')->lockForUpdate()->findOrFail($row->getKey());
                if ($locked->pembanding_id !== null) {
                    $locked->update(['status' => BulkExcelImportRow::STATUS_IMPORTED, 'last_error' => null, 'failure_code' => null]);

                    return $locked;
                }

                $batch = $locked->batch;
                if (! $batch->finalization_date || ! $batch->initiated_by) {
                    throw new BulkExcelImportRowProcessingException(
                        'Informasi finalisasi tidak lengkap. Hubungi pengelola sistem.',
                        'invalid_batch',
                    );
                }

                $previous = BulkExcelImportRow::query()
                    ->where('imported_source_fingerprint', $locked->source_fingerprint)
                    ->whereKeyNot($locked->getKey())
                    ->first();
                if ($previous) {
                    throw new BulkExcelImportRowProcessingException(
                        'Data sumber yang sama sudah pernah dimasukkan sebelumnya.',
                        'source_already_imported',
                        false,
                        $previous->pembanding_id,
                    );
                }

                $image = $this->stagingImage($locked);
                $data = $this->validatedPayload($locked, $image);
                $data['tanggal_data'] = $batch->finalization_date->format('Y-m-d');
                $data['created_by'] = $batch->initiated_by;

                try {
                    $created = $this->savePembanding->create($data, $image);
                } catch (DuplicatePembandingException $exception) {
                    throw new BulkExcelImportRowProcessingException(
                        $exception->getMessage(),
                        'final_duplicate',
                        false,
                        (int) $exception->existing->getKey(),
                    );
                }

                $locked->update([
                    'status' => BulkExcelImportRow::STATUS_IMPORTED,
                    'pembanding_id' => $created->getKey(),
                    'conflicting_pembanding_id' => null,
                    'imported_source_fingerprint' => $locked->source_fingerprint,
                    'last_error' => null,
                    'failure_code' => null,
                ]);

                return $locked->refresh();
            });
        } catch (QueryException $exception) {
            if ($created instanceof Pembanding) {
                Storage::disk('public')->delete($created->image);
            }

            if ($this->isSourceClaimConflict($exception)) {
                $previous = BulkExcelImportRow::query()
                    ->where('imported_source_fingerprint', $row->source_fingerprint)
                    ->first();

                throw new BulkExcelImportRowProcessingException(
                    'Data sumber yang sama sudah pernah dimasukkan sebelumnya.',
                    'source_already_imported',
                    false,
                    $previous?->pembanding_id,
                );
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($created instanceof Pembanding) {
                Storage::disk('public')->delete($created->image);
            }

            throw $exception;
        }

        $this->removeStagingImage($processed);

        return $processed->refresh();
    }

    private function stagingImage(BulkExcelImportRow $row): UploadedFile
    {
        $disk = Storage::disk($row->staging_image_disk ?: 'local');
        if (! $row->staging_image_path || ! $disk->exists($row->staging_image_path)) {
            throw new BulkExcelImportRowProcessingException(
                'Gambar tidak ditemukan. Unggah kembali gambar aset ini.',
                'missing_image',
            );
        }

        return new UploadedFile(
            $disk->path($row->staging_image_path),
            $row->staging_image_original_name ?: basename($row->staging_image_path),
            $row->staging_image_mime,
            UPLOAD_ERR_OK,
            true,
        );
    }

    /** @return array<string, mixed> */
    private function validatedPayload(BulkExcelImportRow $row, UploadedFile $image): array
    {
        $payload = [...($row->mapped_payload ?? []), 'tanggal_data' => $row->batch->finalization_date->format('Y-m-d')];
        $request = PembandingStoreRequest::create('/', 'POST', $payload, [], ['image' => $image]);
        $request->setContainer(app())->setRedirector(app('redirect'));

        try {
            $request->validateResolved();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Data tidak lagi memenuhi aturan isian.';

            throw new BulkExcelImportRowProcessingException($message, 'validation');
        }

        return $request->validated();
    }

    private function removeStagingImage(BulkExcelImportRow $row): void
    {
        if (! $row->staging_image_path) {
            return;
        }

        try {
            $disk = Storage::disk($row->staging_image_disk ?: 'local');
            if (! $disk->exists($row->staging_image_path) || $disk->delete($row->staging_image_path)) {
                $row->update([
                    'staging_image_disk' => null,
                    'staging_image_path' => null,
                    'staging_image_original_name' => null,
                    'staging_image_mime' => null,
                    'staging_image_size' => null,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function isSourceClaimConflict(QueryException $exception): bool
    {
        return in_array(($exception->errorInfo[0] ?? null), ['23000', '19'], true)
            && str_contains($exception->getMessage(), 'imported_source_fingerprint');
    }
}
