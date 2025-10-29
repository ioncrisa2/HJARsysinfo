<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

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
            $this->province->name ?? null
        ])->filter();

        return $parts->isNotEmpty() ? $parts->implode(', ') : null;
    }

    public function getAlamatAttribute(): ?string
    {
        $parts = collect([
            $this->alamat_data,
            $this->district->name ?? null,
            $this->regency->name ?? null
        ])->filter();

        return $parts->isNotEmpty() ? $parts->implode(', ') : null;
    }

    public function getKoordinatStringAttribute(): ?string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    public function getNamaPemberiInfoAttribute(): ?string
    {
        $nama = $this->nama_pemberi_informasi ?? '';

        $status = '';

        if ($this->status_pemberi_informasi) {
            $status = method_exists($this->status_pemberi_informasi, 'getLabel')
                ? $this->status_pemberi_informasi->getLabel()
                : $this->status_pemberi_informasi->value;
        }
        if (empty($nama) && empty($status)) {
            return null;
        }

        if (empty($status)) {
            return $nama;
        }

        return "{$nama}, ({$status})";
    }

    public function getNomerInfoAttribute(): ?string
    {
        $telp  = (string) $this->nomer_telepon_pemberi_informasi;
        $label = $this->jenis_listing?->getLabel() ?? '-';

        return "{$telp} ({$label})";
    }

}
