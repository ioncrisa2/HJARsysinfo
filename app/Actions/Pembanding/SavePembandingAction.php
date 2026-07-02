<?php

namespace App\Actions\Pembanding;

use App\Exceptions\DuplicatePembandingException;
use App\Models\Pembanding;
use App\Services\Pembanding\PembandingFingerprintService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SavePembandingAction
{
    public function __construct(private readonly PembandingFingerprintService $fingerprints) {}

    public function create(array $data, UploadedFile $image): Pembanding
    {
        return $this->persist(new Pembanding, $data, $image);
    }

    public function update(Pembanding $pembanding, array $data, ?UploadedFile $image): Pembanding
    {
        return $this->persist($pembanding, $data, $image);
    }

    private function persist(Pembanding $pembanding, array $data, ?UploadedFile $image): Pembanding
    {
        $checksum = $image
            ? $this->fingerprints->checksumUpload($image)
            : ($pembanding->image_checksum ?: $this->fingerprints->checksumStoredImage($pembanding->image));
        $fingerprint = $this->fingerprints->fingerprint($data, $checksum);
        $this->rejectDuplicate($fingerprint, $pembanding->getKey());

        $newPath = $image ? $this->storeImage($image) : null;

        try {
            return DB::transaction(function () use ($pembanding, $data, $checksum, $fingerprint, $newPath): Pembanding {
                if ($newPath) {
                    $data['image'] = $newPath;
                } else {
                    unset($data['image']);
                }

                $pembanding->fill($data);
                $pembanding->forceFill([
                    'image_checksum' => $checksum,
                    'business_fingerprint' => $fingerprint,
                    'active_fingerprint' => $fingerprint,
                ])->save();

                return $pembanding->refresh();
            });
        } catch (QueryException $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            if ($this->isFingerprintConflict($exception)) {
                $this->rejectDuplicate($fingerprint, $pembanding->getKey());
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }
    }

    private function rejectDuplicate(string $fingerprint, int|string|null $excludeId): void
    {
        $duplicate = Pembanding::withTrashed()
            ->where('business_fingerprint', $fingerprint)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->first();

        if ($duplicate) {
            throw new DuplicatePembandingException($duplicate);
        }
    }

    private function storeImage(UploadedFile $file): string
    {
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('foto_pembanding', strtolower($filename), 'public');

        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan file gambar pembanding.');
        }

        return $path;
    }

    private function isFingerprintConflict(QueryException $exception): bool
    {
        return in_array(($exception->errorInfo[0] ?? null), ['23000', '19'], true)
            && (str_contains($exception->getMessage(), 'dp_active_fingerprint_unique')
                || str_contains($exception->getMessage(), 'active_fingerprint'));
    }
}
