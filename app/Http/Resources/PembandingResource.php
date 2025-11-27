<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PembandingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'jenis_listing' => [
                'value' => $this->jenis_listing?->value,
                'label' => $this->jenis_listing?->getLabel(), // kalau enum-nya pakai HasLabel
            ],
            'jenis_objek' => [
                'value' => $this->jenis_objek?->value,
                'label' => $this->jenis_objek?->getLabel(),
            ],
            'peruntukan' => [
                'value' => $this->peruntukan?->value,
                'label' => $this->peruntukan?->getLabel(),
            ],
            'bentuk_tanah' => [
                'value' => $this->bentuk_tanah?->value,
                'label' => $this->bentuk_tanah?->getLabel(),
            ],
            'dokumen_tanah' => [
                'value' => $this->dokumen_tanah?->value,
                'label' => $this->dokumen_tanah?->getLabel(),
            ],
            'posisi_tanah' => [
                'value' => $this->posisi_tanah?->value,
                'label' => $this->posisi_tanah?->getLabel(),
            ],
            'kondisi_tanah' => [
                'value' => $this->kondisi_tanah?->value,
                'label' => $this->kondisi_tanah?->getLabel(),
            ],
            'status_pemberi_informasi' => [
                'value' => $this->status_pemberi_informasi?->value,
                'label' => $this->status_pemberi_informasi?->getLabel(),
            ],
            'topografi' => [
                'value' => $this->topografi?->value,
                'label' => $this->topografi?->getLabel(),
            ],

            'nama_pemberi_informasi'         => $this->nama_pemberi_informasi,
            'nomer_telepon_pemberi_informasi'=> $this->nomer_telepon_pemberi_informasi,
            'luas_tanah'                     => $this->luas_tanah,
            'luas_bangunan'                  => $this->luas_bangunan,
            'tahun_bangun'                   => $this->tahun_bangun,
            'lebar_depan'                    => $this->lebar_depan,
            'lebar_jalan'                    => $this->lebar_jalan,
            'rasio_tapak'                    => $this->rasio_tapak,
            'harga'                          => $this->harga,
            'tanggal_data'                   => optional($this->tanggal_data)->toDateString(),
            'catatan'                        => $this->catatan,

            // Lokasi administrasi
            'province' => [
                'id'   => $this->province?->id,
                'name' => $this->province?->name,
            ],
            'regency' => [
                'id'   => $this->regency?->id,
                'name' => $this->regency?->name,
            ],
            'district' => [
                'id'   => $this->district?->id,
                'name' => $this->district?->name,
            ],
            'village' => [
                'id'   => $this->village?->id,
                'name' => $this->village?->name,
            ],

            'alamat_data' => $this->alamat_data,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,

            'image_url'   => $this->image
                ? (filter_var($this->image, FILTER_VALIDATE_URL)
                    ? $this->image
                    : Storage::url($this->image))
                : null,

            'created_by' => [
                'id'   => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
        ];
    }
}
