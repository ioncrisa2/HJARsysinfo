<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DokumenTanah: string implements HasLabel
{
    case SertifikatHakMilik         = 'sertifikat_hak_milik';
    case SertifikatHakGunaBangunan  = 'sertifikat_hak_guna_bangunan';
    case SertifikatHakGunaUsaha     = 'sertifikat_hak_guna_usaha';
    case AktaJualBeli               = 'akta_jual_beli';
    case Girik                      = 'girik';
    case PetokDesa                  = 'petok_desa';
    case SuratCamat                 = 'surat_camat';
    case PetaBidangTanah            = 'peta_bidang_tanah';
    case Lainnya                    = 'lainnya';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SertifikatHakMilik        => 'Sertifikat Hak Milik (SHM)',
            self::SertifikatHakGunaBangunan => 'Sertifikat Hak Guna Bangunan (HGB)',
            self::SertifikatHakGunaUsaha    => 'Sertifikat Hak Guna Usaha (HGU)',
            self::AktaJualBeli              => 'Akta Jual Beli (AJB)',
            self::Girik                     => 'Girik',
            self::PetokDesa                 => 'Petok D',
            self::SuratCamat                => 'Surat Camat',
            self::PetaBidangTanah           => 'Peta Bidang Tanah (PBT)',
            self::Lainnya                   => 'Lainnya',
        };
    }
}
