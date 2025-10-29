<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisObjek: string implements HasLabel
{
    case TANAH = 'tanah';
    case RUMAH_TINGGAL = 'rumah_tinggal';
    case RUKO = 'ruko';
    case APARTEMENT = 'apartement';
    case KIOS = 'kios';
    case GUDANG = 'gudang';
    case KANTOR = 'kantor';
    case PABRIK = 'pabrik';
    case TANAH_KEBUN = 'tanah_kebun';
    case TANAH_DAN_BANGUNAN = 'tanah_dan_bangunan';
    case SAWAH = 'sawah';

    public function getLabel(): ?string
    {
        return match ($this){
            self::TANAH => 'Tanah',
            self::RUMAH_TINGGAL => 'Rumah Tinggal',
            self::RUKO => 'Ruko',
            self::APARTEMENT => 'Apartement',
            self::KIOS => 'Kios',
            self::GUDANG => 'Gudang',
            self::KANTOR => 'Kantor',
            self::PABRIK => 'Pabrik',
            self::TANAH_KEBUN => 'Tanah Kebun',
            self::TANAH_DAN_BANGUNAN => 'Tanah dan Bangunan',
            self::SAWAH => 'Sawah',
        };
    }
}
