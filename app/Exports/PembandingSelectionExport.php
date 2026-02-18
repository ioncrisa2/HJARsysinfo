<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PembandingSelectionExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Pemberi Informasi',
            'Nomor Telepon',
            'Alamat',
            'Provinsi',
            'Kab/Kota',
            'Kecamatan',
            'Kelurahan',
            'Jenis Listing',
            'Jenis Objek',
            'Status Pemberi Informasi',
            'Luas Tanah (m²)',
            'Luas Bangunan (m²)',
            'Harga',
            'Tanggal Data',
            'Latitude',
            'Longitude',
            'Foto (URL)',
        ];
    }

    /**
     * @param  mixed  $row
     * @return array
     */
    public function map($row): array
    {
        $imageUrl = $row->image ? Storage::disk('public')->url($row->image) : null;

        return [
            $row->id,
            $row->nama_pemberi_informasi,
            $row->nomer_telepon_pemberi_informasi,
            $row->alamat_data,
            optional($row->province)->name,
            optional($row->regency)->name,
            optional($row->district)->name,
            optional($row->village)->name,
            optional($row->jenisListing)->name,
            optional($row->jenisObjek)->name,
            optional($row->statusPemberiInformasi)->name,
            $row->luas_tanah,
            $row->luas_bangunan,
            $row->harga,
            optional($row->tanggal_data)?->format('Y-m-d'),
            $row->latitude,
            $row->longitude,
            $imageUrl,
        ];
    }
}
