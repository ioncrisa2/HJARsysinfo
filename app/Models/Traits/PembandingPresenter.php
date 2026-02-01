<?php

namespace App\Models\Traits;

use App\Models\JenisListing as JenisListingModel;
use App\Models\StatusPemberiInformasi as StatusPemberiInformasiModel;

trait PembandingPresenter
{
    public function getAlamatLengkapAttribute(): ?string
    {
        $parts = collect([
            $this->alamat_data,
            $this->village->name ?? null,
            $this->district->name ?? null,
            $this->regency->name ?? null,
            $this->province->name ?? null,
        ])->filter();

        return $parts->isNotEmpty() ? $parts->implode(', ') : null;
    }

    public function getAlamatSingkatAttribute(): ?string
    {
        $parts = collect([
            $this->alamat_data,
            $this->district->name ?? null,
            $this->province->name ?? null,
        ])->filter();

        return $parts->isNotEmpty() ? $parts->implode(', ') : null;
    }

    public function getAlamatAttribute(): ?string
    {
        $parts = collect([
            $this->alamat_data,
            $this->district->name ?? null,
            $this->regency->name ?? null,
        ])->filter();

        return $parts->isNotEmpty() ? $parts->implode(', ') : null;
    }

    public function getKoordinatStringAttribute(): ?string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    public function getNamaPemberiInfoAttribute(): ?string
    {
        $nama = trim((string) ($this->nama_pemberi_informasi ?? ''));

        // Prefer relation (new)
        $status = trim((string) ($this->statusPemberiInformasi?->name ?? ''));

        // Fallback to legacy slug (old)
        if ($status === '') {
            $slug = $this->status_pemberi_informasi; // legacy string slug
            if ($slug) {
                static $statusMap = null;
                $statusMap ??= StatusPemberiInformasiModel::query()->pluck('name', 'slug')->all();
                $status = $statusMap[$slug] ?? (string) $slug;
            }
        }

        if ($nama === '' && $status === '') {
            return null;
        }

        if ($status === '') {
            return $nama;
        }

        if ($nama === '') {
            return $status;
        }

        return "{$nama}, ({$status})";
    }

    public function getNomerInfoAttribute(): ?string
    {
        $telp = trim((string) ($this->nomer_telepon_pemberi_informasi ?? ''));

        // Prefer relation (new)
        $label = trim((string) ($this->jenisListing?->name ?? ''));

        // Fallback to legacy slug (old)
        if ($label === '') {
            $slug = $this->jenis_listing; // legacy string slug
            if ($slug) {
                static $listingMap = null;
                $listingMap ??= JenisListingModel::query()->pluck('name', 'slug')->all();
                $label = $listingMap[$slug] ?? (string) $slug;
            }
        }

        if ($telp === '' && $label === '') {
            return null;
        }

        $label = $label !== '' ? $label : '-';

        return $telp !== '' ? "{$telp} ({$label})" : "({$label})";
    }
}
