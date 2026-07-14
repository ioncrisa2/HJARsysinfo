<?php

namespace App\Actions\Pembanding;

use App\Models\Pembanding;
use App\Models\PembandingDuplicateSubmission;
use App\Services\Pembanding\PembandingFingerprintService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreparePembandingDuplicateReviewAction
{
    public function __construct(private readonly PembandingFingerprintService $fingerprints) {}

    public function execute(int $userId, array $data, UploadedFile $image): ?PembandingDuplicateSubmission
    {
        $checksum = $this->fingerprints->checksumUpload($image);
        $fingerprint = $this->fingerprints->fingerprint($data, $checksum);
        $payload = $data;
        unset($payload['image']);
        $candidates = Pembanding::withTrashed()
            ->where('business_fingerprint', $fingerprint)
            ->orderByRaw('deleted_at IS NULL DESC')
            ->orderByDesc('updated_at')
            ->get(['id', 'updated_at']);

        if ($candidates->isEmpty()) {
            return null;
        }

        $id = (string) Str::uuid();
        $extension = strtolower($image->getClientOriginalExtension() ?: 'bin');
        $path = $image->storeAs('pembanding-duplicate-submissions', "{$id}.{$extension}", 'local');

        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan data pembanding sementara.');
        }

        try {
            return DB::transaction(fn (): PembandingDuplicateSubmission => PembandingDuplicateSubmission::create([
                'id' => $id,
                'user_id' => $userId,
                'payload' => $payload,
                'image_path' => $path,
                'image_original_name' => $image->getClientOriginalName(),
                'image_mime_type' => $image->getMimeType() ?: 'application/octet-stream',
                'fingerprint' => $fingerprint,
                'candidate_versions' => $candidates->mapWithKeys(fn (Pembanding $candidate): array => [
                    (string) $candidate->id => $candidate->updated_at?->toISOString(),
                ])->all(),
                'expires_at' => now()->addDay(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
