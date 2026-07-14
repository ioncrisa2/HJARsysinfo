<?php

namespace App\Actions\BulkExcelImport;

use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use App\Models\User;
use App\Services\BulkExcelImport\BulkExcelImportRowMapper;
use App\Services\BulkExcelImport\BulkExcelImportValueNormalizer;
use App\Services\BulkExcelImport\BulkExcelImportWorkbookParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CreateBulkExcelImportBatchAction
{
    public function __construct(
        private readonly BulkExcelImportWorkbookParser $parser,
        private readonly BulkExcelImportRowMapper $mapper,
        private readonly BulkExcelImportValueNormalizer $normalizer,
    ) {}

    /** @return array{batch: BulkExcelImportBatch, existing: bool} */
    public function execute(User $owner, UploadedFile $file): array
    {
        $checksum = hash_file('sha256', $file->getRealPath());
        $existing = BulkExcelImportBatch::query()
            ->where('owner_id', $owner->id)
            ->where('file_checksum', $checksum)
            ->first();

        if ($existing) {
            return ['batch' => $existing, 'existing' => true];
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $path = 'bulk-excel-imports/'.$owner->id.'/'.Str::uuid().'.'.$extension;
        $stored = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
        if ($stored === false) {
            throw new \RuntimeException('File Excel gagal disimpan. Coba unggah kembali.');
        }

        try {
            $parsed = $this->parser->parse(Storage::disk('local')->path($path));
            $batch = DB::transaction(function () use ($owner, $file, $checksum, $path, $parsed): BulkExcelImportBatch {
                $batch = BulkExcelImportBatch::query()->create([
                    'owner_id' => $owner->id,
                    'original_filename' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'source_disk' => 'local',
                    'source_path' => $path,
                    'file_checksum' => $checksum,
                    'sheet_name' => $parsed['sheet_name'],
                    'status' => BulkExcelImportBatch::STATUS_DRAFT,
                    'last_activity_at' => now(),
                ]);

                $seen = [];
                $selected = 0;
                $ready = 0;
                foreach ($parsed['rows'] as $sourceRow) {
                    $fingerprint = $this->normalizer->fingerprint($sourceRow['values']);
                    $mapped = $this->mapper->map($sourceRow['values']);
                    $duplicateOf = $seen[$fingerprint] ?? null;
                    $status = $duplicateOf
                        ? BulkExcelImportRow::STATUS_DUPLICATE
                        : ($mapped['warnings'] !== []
                            ? BulkExcelImportRow::STATUS_NEEDS_CONFIRMATION
                            : ($mapped['missing'] !== [] ? BulkExcelImportRow::STATUS_INCOMPLETE : BulkExcelImportRow::STATUS_READY));

                    $row = $batch->rows()->create([
                        'source_row_number' => $sourceRow['row_number'],
                        'source_fingerprint' => $fingerprint,
                        'status' => $status,
                        'is_selected' => $duplicateOf === null,
                        'raw_payload' => $sourceRow['values'],
                        'mapped_payload' => $mapped['mapped'],
                        'missing_fields' => $mapped['missing'],
                        'warnings' => $duplicateOf ? [[
                            'field' => 'duplicate',
                            'message' => 'Data yang sama sudah ada pada baris '.$duplicateOf->source_row_number.'.',
                        ]] : $mapped['warnings'],
                        'duplicate_of_row_id' => $duplicateOf?->id,
                    ]);

                    $seen[$fingerprint] ??= $row;
                    $selected += $row->is_selected ? 1 : 0;
                    $ready += $row->status === BulkExcelImportRow::STATUS_READY ? 1 : 0;
                }

                $batch->update([
                    'total_rows' => count($parsed['rows']),
                    'selected_rows' => $selected,
                    'ready_rows' => $ready,
                ]);

                return $batch->refresh();
            });

            return ['batch' => $batch, 'existing' => false];
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }
}
