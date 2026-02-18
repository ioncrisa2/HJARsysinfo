<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PembandingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'jenis_listing' => [
                'id' => $this->jenisListing?->id,
                'slug' => $this->jenisListing?->slug,
                'name' => $this->jenisListing?->name,
            ],

            'jenis_objek' => [
                'id' => $this->jenisObjek?->id,
                'slug' => $this->jenisObjek?->slug,
                'name' => $this->jenisObjek?->name,
            ],

            'peruntukan' => [
                'id' => $this->peruntukanRef?->id,
                'slug' => $this->peruntukanRef?->slug,
                'name' => $this->peruntukanRef?->name,
            ],

            'bentuk_tanah' => [
                'id' => $this->bentukTanah?->id,
                'slug' => $this->bentukTanah?->slug,
                'name' => $this->bentukTanah?->name,
            ],

            'dokumen_tanah' => [
                'id' => $this->dokumenTanah?->id,
                'slug' => $this->dokumenTanah?->slug,
                'name' => $this->dokumenTanah?->name,
            ],

            'posisi_tanah' => [
                'id' => $this->posisiTanah?->id,
                'slug' => $this->posisiTanah?->slug,
                'name' => $this->posisiTanah?->name,
            ],

            'kondisi_tanah' => [
                'id' => $this->kondisiTanah?->id,
                'slug' => $this->kondisiTanah?->slug,
                'name' => $this->kondisiTanah?->name,
            ],

            'status_pemberi_informasi' => [
                'id' => $this->statusPemberiInformasi?->id,
                'slug' => $this->statusPemberiInformasi?->slug,
                'name' => $this->statusPemberiInformasi?->name,
            ],

            'topografi' => [
                'id' => $this->topografiRef?->id,
                'slug' => $this->topografiRef?->slug,
                'name' => $this->topografiRef?->name,
            ],

            'nama_pemberi_informasi' => $this->nama_pemberi_informasi,
            'nomer_telepon_pemberi_informasi' => $this->nomer_telepon_pemberi_informasi,
            'luas_tanah' => $this->luas_tanah,
            'luas_bangunan' => $this->luas_bangunan,
            'tahun_bangun' => $this->tahun_bangun,
            'lebar_depan' => $this->lebar_depan,
            'lebar_jalan' => $this->lebar_jalan,
            'rasio_tapak' => $this->rasio_tapak,
            'harga' => $this->harga,
            'tanggal_data' => $this->tanggal_data,
            'catatan' => $this->catatan,

            'province' => [
                'id' => $this->province?->id,
                'name' => $this->province?->name,
            ],
            'regency' => [
                'id' => $this->regency?->id,
                'name' => $this->regency?->name,
            ],
            'district' => [
                'id' => $this->district?->id,
                'name' => $this->district?->name,
            ],
            'village' => [
                'id' => $this->village?->id,
                'name' => $this->village?->name,
            ],

            'alamat_data' => $this->alamat_data,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            // 'image' => $this->image,
            'image_url' => $this->image_url,

            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
        ];
    }

}
