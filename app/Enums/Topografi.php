<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Topografi: string implements HasLabel
{
    case DatarLebihTinggiDariJalan = 'datar_lebih_tinggi_dari_jalan';
    case DatarLebihRendahDariJalan = 'datar_lebih_rendah_dari_jalan';
    case DatarDenganJalan          = 'datar_dengan_jalan';
    case Berbukit                  = 'berbukit';
    case Melandai                  = 'melandai';
    case BerbukitDanMelandai       = 'berbukit_dan_melandai';
    case Bervariasi                = 'bervariasi';
    case Lainnya                   = 'lainnya';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DatarLebihTinggiDariJalan => 'Datar, Lebih Tinggi Dari Jalan',
            self::DatarLebihRendahDariJalan => 'Datar, Lebih Rendah Dari Jalan',
            self::DatarDenganJalan          => 'Datar, Sama dengan Jalan',
            self::Berbukit                  => 'Berbukit',
            self::Melandai                  => 'Melandai',
            self::BerbukitDanMelandai       => 'Berbukit dan Melandai',
            self::Bervariasi                => 'Bervariasi',
            self::Lainnya                   => 'Lainnya',
        };
    }
}
