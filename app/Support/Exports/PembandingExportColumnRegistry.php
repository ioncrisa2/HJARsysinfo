<?php

namespace App\Support\Exports;

use App\Models\Pembanding;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PembandingExportColumnRegistry
{
    public const DEFAULT_PROFILE = 'lengkap';

    /** @return array<string, array{label: string, columns: array<int, string>}> */
    public function profiles(): array
    {
        return [
            'ringkas' => [
                'label' => 'Ringkas',
                'columns' => ['id', 'alamat', 'province', 'regency', 'jenis_listing', 'jenis_objek', 'luas_tanah', 'luas_bangunan', 'harga', 'tanggal_data'],
            ],
            'lengkap' => [
                'label' => 'Analisis Lengkap',
                'columns' => [
                    'id', 'alamat', 'province', 'regency', 'district', 'village', 'jenis_listing', 'jenis_objek',
                    'status_pemberi_informasi', 'luas_tanah', 'luas_bangunan', 'tahun_bangun', 'lebar_depan',
                    'lebar_jalan', 'rasio_tapak', 'harga', 'periode_sewa', 'tanggal_data', 'latitude', 'longitude',
                    'bentuk_tanah', 'kondisi_tanah', 'posisi_tanah', 'topografi', 'dokumen_tanah', 'peruntukan',
                    'catatan', 'foto_url',
                ],
            ],
            'kontak' => [
                'label' => 'Kontak',
                'columns' => ['id', 'nama_pemberi_informasi', 'nomor_telepon', 'status_pemberi_informasi', 'alamat', 'jenis_objek', 'tanggal_data'],
            ],
            'geospasial' => [
                'label' => 'Geospasial',
                'columns' => ['id', 'alamat', 'province', 'regency', 'district', 'village', 'latitude', 'longitude', 'jenis_listing', 'jenis_objek', 'harga', 'luas_tanah', 'tanggal_data'],
            ],
            'audit' => [
                'label' => 'Audit',
                'columns' => ['id', 'alamat', 'created_by', 'updated_by', 'created_at', 'updated_at'],
            ],
        ];
    }

    /** @return array<string, array{label: string, type: string, sensitive?: bool}> */
    public function columns(): array
    {
        return [
            'id' => ['label' => 'ID', 'type' => 'integer'],
            'nama_pemberi_informasi' => ['label' => 'Nama Pemberi Informasi', 'type' => 'string', 'sensitive' => true],
            'nomor_telepon' => ['label' => 'Nomor Telepon', 'type' => 'string', 'sensitive' => true],
            'alamat' => ['label' => 'Alamat', 'type' => 'string'],
            'province' => ['label' => 'Provinsi', 'type' => 'string'],
            'regency' => ['label' => 'Kabupaten / Kota', 'type' => 'string'],
            'district' => ['label' => 'Kecamatan', 'type' => 'string'],
            'village' => ['label' => 'Desa / Kelurahan', 'type' => 'string'],
            'jenis_listing' => ['label' => 'Jenis Listing', 'type' => 'string'],
            'jenis_objek' => ['label' => 'Jenis Objek', 'type' => 'string'],
            'status_pemberi_informasi' => ['label' => 'Status Pemberi Informasi', 'type' => 'string'],
            'luas_tanah' => ['label' => 'Luas Tanah (m²)', 'type' => 'decimal'],
            'luas_bangunan' => ['label' => 'Luas Bangunan (m²)', 'type' => 'decimal'],
            'tahun_bangun' => ['label' => 'Tahun Bangun', 'type' => 'integer'],
            'lebar_depan' => ['label' => 'Lebar Depan (m)', 'type' => 'decimal'],
            'lebar_jalan' => ['label' => 'Lebar Jalan (m)', 'type' => 'decimal'],
            'rasio_tapak' => ['label' => 'Rasio Tapak', 'type' => 'string'],
            'harga' => ['label' => 'Harga', 'type' => 'currency'],
            'periode_sewa' => ['label' => 'Periode Harga Sewa', 'type' => 'string'],
            'tanggal_data' => ['label' => 'Tanggal Data', 'type' => 'date'],
            'latitude' => ['label' => 'Latitude', 'type' => 'coordinate'],
            'longitude' => ['label' => 'Longitude', 'type' => 'coordinate'],
            'bentuk_tanah' => ['label' => 'Bentuk Tanah', 'type' => 'string'],
            'kondisi_tanah' => ['label' => 'Kondisi Tanah', 'type' => 'string'],
            'posisi_tanah' => ['label' => 'Posisi Tanah', 'type' => 'string'],
            'topografi' => ['label' => 'Topografi', 'type' => 'string'],
            'dokumen_tanah' => ['label' => 'Dokumen Tanah', 'type' => 'string'],
            'peruntukan' => ['label' => 'Peruntukan', 'type' => 'string'],
            'catatan' => ['label' => 'Catatan', 'type' => 'string'],
            'foto_url' => ['label' => 'Foto (URL)', 'type' => 'string'],
            'quality_issues' => ['label' => 'Masalah Kelengkapan', 'type' => 'string'],
            'created_by' => ['label' => 'Dibuat Oleh', 'type' => 'string'],
            'updated_by' => ['label' => 'Diperbarui Oleh', 'type' => 'string'],
            'created_at' => ['label' => 'Dibuat Pada', 'type' => 'datetime'],
            'updated_at' => ['label' => 'Diperbarui Pada', 'type' => 'datetime'],
        ];
    }

    /** @return array<int, string> */
    public function resolveColumns(User $user, ?string $profile, array $requested = []): array
    {
        $profile = array_key_exists((string) $profile, $this->profiles()) ? (string) $profile : self::DEFAULT_PROFILE;
        $allowed = collect($this->columns())
            ->reject(fn (array $definition): bool => ($definition['sensitive'] ?? false) && ! $user->can('export_sensitive_data::pembanding'))
            ->keys();
        $columns = $requested !== [] ? collect($requested) : collect($this->profiles()[$profile]['columns']);

        return $columns
            ->filter(fn ($column): bool => is_string($column) && $allowed->contains($column))
            ->unique()
            ->values()
            ->all();
    }

    public function publicConfiguration(User $user): array
    {
        $availableColumns = collect($this->columns())
            ->reject(fn (array $definition): bool => ($definition['sensitive'] ?? false) && ! $user->can('export_sensitive_data::pembanding'));

        return [
            'profiles' => collect($this->profiles())->map(function (array $profile, string $key) use ($user): array {
                return [
                    'value' => $key,
                    'label' => $profile['label'],
                    'columns' => $this->resolveColumns($user, $key),
                ];
            })->values()->all(),
            'columns' => $availableColumns->map(fn (array $definition, string $key): array => [
                'value' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
            ])->values()->all(),
        ];
    }

    /** @return array<int, string> */
    public function headings(array $columns): array
    {
        $definitions = $this->columns();

        return collect($columns)->map(fn (string $column): string => $definitions[$column]['label'])->all();
    }

    /** @return array<int, mixed> */
    public function map(Pembanding $record, array $columns, bool $spreadsheetSafe = false): array
    {
        return collect($columns)->map(function (string $column) use ($record, $spreadsheetSafe): mixed {
            $value = $this->value($record, $column);

            return $spreadsheetSafe && is_string($value) ? $this->spreadsheetSafe($value) : $value;
        })->all();
    }

    public function value(Pembanding $record, string $column): mixed
    {
        return match ($column) {
            'id' => $record->id,
            'nama_pemberi_informasi' => $record->nama_pemberi_informasi,
            'nomor_telepon' => $record->nomer_telepon_pemberi_informasi,
            'alamat' => $record->alamat_data,
            'province' => $record->province?->name,
            'regency' => $record->regency?->name,
            'district' => $record->district?->name,
            'village' => $record->village?->name,
            'jenis_listing' => $record->jenisListing?->name,
            'jenis_objek' => $record->jenisObjek?->name,
            'status_pemberi_informasi' => $record->statusPemberiInformasi?->name,
            'luas_tanah' => $record->luas_tanah,
            'luas_bangunan' => $record->luas_bangunan,
            'tahun_bangun' => $record->tahun_bangun,
            'lebar_depan' => $record->lebar_depan,
            'lebar_jalan' => $record->lebar_jalan,
            'rasio_tapak' => $record->rasio_tapak,
            'harga' => $record->harga,
            'periode_sewa' => $record->sewa_periode_label,
            'tanggal_data' => $this->date($record->getRawOriginal('tanggal_data')),
            'latitude' => $record->latitude,
            'longitude' => $record->longitude,
            'bentuk_tanah' => $record->bentukTanah?->name,
            'kondisi_tanah' => $record->kondisiTanah?->name,
            'posisi_tanah' => $record->posisiTanah?->name,
            'topografi' => $record->topografiRef?->name,
            'dokumen_tanah' => $record->dokumenTanah?->name,
            'peruntukan' => $record->peruntukanRef?->name,
            'catatan' => $record->catatan,
            'foto_url' => $record->image ? Storage::disk('public')->url($record->image) : null,
            'quality_issues' => $this->qualityIssues($record),
            'created_by' => $record->creator?->name,
            'updated_by' => $record->updater?->name,
            'created_at' => optional($record->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($record->updated_at)->format('Y-m-d H:i:s'),
            default => null,
        };
    }

    public function displayValue(Pembanding $record, string $column): string
    {
        $value = $this->value($record, $column);
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($this->columns()[$column]['type']) {
            'currency' => 'Rp '.number_format((float) $value, 0, ',', '.'),
            'decimal' => number_format((float) $value, 2, ',', '.'),
            'coordinate' => number_format((float) $value, 6, '.', ''),
            default => (string) $value,
        };
    }

    private function date(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/u', ltrim($value)) === 1 ? "'{$value}" : $value;
    }

    private function qualityIssues(Pembanding $record): string
    {
        $inactiveReferences = collect([
            $record->jenisListing, $record->jenisObjek, $record->statusPemberiInformasi,
            $record->bentukTanah, $record->dokumenTanah, $record->posisiTanah,
            $record->kondisiTanah, $record->topografiRef, $record->peruntukanRef,
        ])->filter(fn ($reference): bool => $reference && ! $reference->is_active)->pluck('name');

        return collect([
            (! is_numeric($record->latitude) || ! is_numeric($record->longitude)) ? 'Tanpa koordinat' : null,
            blank($record->image) ? 'Tanpa foto' : null,
            ! is_numeric($record->harga) || (float) $record->harga <= 0 ? 'Tanpa harga' : null,
            ! is_numeric($record->luas_tanah) || (float) $record->luas_tanah <= 0 ? 'Tanpa luas tanah' : null,
            $record->tanggal_data && Carbon::parse($record->tanggal_data)->lt(now()->subYears(2)) ? 'Data lebih dari 2 tahun' : null,
            $inactiveReferences->isNotEmpty() ? 'Referensi nonaktif: '.$inactiveReferences->implode(', ') : null,
        ])->filter()->implode('; ');
    }
}
