<?php

namespace App\Actions\P2pk;

use App\Actions\Pembanding\SavePembandingAction;
use App\Exceptions\DuplicatePembandingException;
use App\Exceptions\P2pkImportRowProcessingException;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Models\P2pkImportRow;
use App\Models\Pembanding;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessP2pkImportRowAction
{
    public function __construct(private readonly SavePembandingAction $savePembanding) {}

    public function execute(P2pkImportRow $row): P2pkImportRow
    {
        $created = null;

        try {
            $processed = DB::transaction(function () use ($row, &$created): P2pkImportRow {
                $locked = P2pkImportRow::query()->with('batch.initiatedBy')->lockForUpdate()->findOrFail($row->getKey());
                if ($locked->pembanding_id !== null) {
                    $locked->update(['status' => P2pkImportRow::STATUS_IMPORTED, 'last_error' => null, 'failure_code' => null]);

                    return $locked;
                }

                $batch = $locked->batch;
                if (! $batch->finalization_date || ! $batch->initiated_by) {
                    throw new P2pkImportRowProcessingException(
                        'Informasi finalisasi tidak lengkap. Hubungi pengelola sistem.',
                        'invalid_batch',
                    );
                }

                $previous = P2pkImportRow::query()
                    ->where('imported_source_fingerprint', $locked->source_fingerprint)
                    ->whereKeyNot($locked->getKey())
                    ->first();
                if ($previous) {
                    throw new P2pkImportRowProcessingException(
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
                    throw new P2pkImportRowProcessingException(
                        $exception->getMessage(),
                        'final_duplicate',
                        false,
                        (int) $exception->existing->getKey(),
                    );
                }

                $locked->update([
                    'status' => P2pkImportRow::STATUS_IMPORTED,
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
                $previous = P2pkImportRow::query()
                    ->where('imported_source_fingerprint', $row->source_fingerprint)
                    ->first();

                throw new P2pkImportRowProcessingException(
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

    private function stagingImage(P2pkImportRow $row): UploadedFile
    {
        $disk = Storage::disk($row->staging_image_disk ?: 'local');
        if (! $row->staging_image_path || ! $disk->exists($row->staging_image_path)) {
            throw new P2pkImportRowProcessingException(
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
    private function validatedPayload(P2pkImportRow $row, UploadedFile $image): array
    {
        $payload = [...($row->mapped_payload ?? []), 'tanggal_data' => $row->batch->finalization_date->format('Y-m-d')];
        $request = PembandingStoreRequest::create('/', 'POST', $payload, [], ['image' => $image]);
        $request->setContainer(app())->setRedirector(app('redirect'));

        try {
            $request->validateResolved();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Data tidak lagi memenuhi aturan isian.';

            throw new P2pkImportRowProcessingException($message, 'validation');
        }

        return $request->validated();
    }

    private function removeStagingImage(P2pkImportRow $row): void
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
            && (str_contains($exception->getMessage(), 'p2pk_import_source_unique')
                || str_contains($exception->getMessage(), 'imported_source_fingerprint'));
    }
}
