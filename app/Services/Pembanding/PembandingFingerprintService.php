<?php

namespace App\Services\Pembanding;

use App\Models\Pembanding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PembandingFingerprintService
{
    private const FIELDS = [
        'jenis_listing_id', 'jenis_objek_id', 'status_pemberi_informasi_id',
        'bentuk_tanah_id', 'dokumen_tanah_id', 'posisi_tanah_id',
        'kondisi_tanah_id', 'topografi_id', 'peruntukan_id',
        'nama_pemberi_informasi', 'nomer_telepon_pemberi_informasi',
        'tanggal_data', 'alamat_data', 'province_id', 'regency_id',
        'district_id', 'village_id', 'latitude', 'longitude', 'luas_tanah',
        'luas_bangunan', 'tahun_bangun', 'lebar_depan', 'lebar_jalan',
        'rasio_tapak', 'harga', 'jangka_waktu_sewa', 'satuan_waktu_sewa',
        'catatan',
    ];

    private const TEXT_FIELDS = [
        'nama_pemberi_informasi', 'alamat_data', 'rasio_tapak',
        'satuan_waktu_sewa', 'catatan',
    ];

    private const DECIMAL_SCALES = [
        'latitude' => 6, 'longitude' => 6, 'luas_tanah' => 2,
        'luas_bangunan' => 2, 'lebar_depan' => 2, 'lebar_jalan' => 2,
        'jangka_waktu_sewa' => 2,
    ];

    public function fingerprint(array|Pembanding $source, ?string $imageChecksum): string
    {
        $values = $source instanceof Pembanding ? $source->getAttributes() : $source;
        $canonical = [];

        foreach (self::FIELDS as $field) {
            $canonical[$field] = $this->normalize($field, $values[$field] ?? null);
        }

        $canonical['image_checksum'] = $imageChecksum;

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function checksumUpload(UploadedFile $file): string
    {
        $checksum = hash_file('sha256', $file->getRealPath());

        if ($checksum === false) {
            throw new RuntimeException('Gagal menghitung checksum file gambar yang diunggah.');
        }

        return $checksum;
    }

    public function checksumStoredImage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return hash('sha256', "missing:{$path}");
        }

        $stream = $disk->readStream($path);
        if ($stream === false) {
            throw new RuntimeException("Gagal membaca file gambar: {$path}");
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }

    private function normalize(string $field, mixed $value): int|string|null
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if ($field === 'nomer_telepon_pemberi_informasi') {
            return $this->normalizePhone($value);
        }

        if ($field === 'tanggal_data') {
            return Carbon::parse($value)->format('Y-m-d');
        }

        if (isset(self::DECIMAL_SCALES[$field])) {
            return number_format((float) $value, self::DECIMAL_SCALES[$field], '.', '');
        }

        if ($field === 'harga') {
            return number_format((float) $value, 0, '.', '');
        }

        if (
            $field === 'tahun_bangun'
            || (
                str_ends_with($field, '_id')
                && ! in_array($field, ['province_id', 'regency_id', 'district_id', 'village_id'], true)
            )
        ) {
            return (int) $value;
        }

        if (in_array($field, self::TEXT_FIELDS, true)) {
            return $this->normalizeText((string) $value);
        }

        return (string) $value;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        $digits = preg_replace('/^(?:62|0)/', '', $digits ?? '');

        return $digits === '' ? null : $digits;
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value));
    }
}
