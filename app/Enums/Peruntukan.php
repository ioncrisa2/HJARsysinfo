<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Peruntukan: string implements HasLabel
{
    case UnitApartemen = 'unit_apartemen';
    case RumahTinggal  = 'rumah_tinggal';
    case Ruko          = 'ruko';
    case Perkantoran   = 'perkantoran';
    case Kios          = 'kios';
    case Gudang        = 'gudang';
    case Pabrik        = 'pabrik';
    case TanahKosong   = 'tanah_kosong';
    case Rukan         = 'rukan';
    case Townhouse     = 'townhouse';
    case Villa         = 'villa';
    case Mall          = 'mall';
    case Campuran      = 'campuran';
    case Lainnya       = 'lainnya';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UnitApartemen => 'Unit Apartemen',
            self::RumahTinggal  => 'Rumah Tinggal',
            self::Ruko          => 'Ruko',
            self::Perkantoran   => 'Perkantoran',
            self::Kios          => 'Kios',
            self::Gudang        => 'Gudang',
            self::Pabrik        => 'Pabrik',
            self::TanahKosong   => 'Tanah Kosong',
            self::Rukan         => 'Rukan',
            self::Townhouse     => 'Town House',
            self::Villa         => 'Villa',
            self::Mall          => 'Mall',
            self::Campuran      => 'Campuran',
            self::Lainnya       => 'Lainnya',
        };
    }
}
