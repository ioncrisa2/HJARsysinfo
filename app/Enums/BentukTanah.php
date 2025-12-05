<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BentukTanah: string implements HasLabel
{
    case PersegiPanjang  = 'persegi_panjang';
    case Persegi         = 'persegi';
    case Trapesium       = 'trapesium';
    case Segitiga        = 'segitiga';
    case Lingkaran       =   'lingkaran';
    case TidakBeraturan  = 'tidak_beraturan';
    case LetterL         = 'letter_l';
    case Lainnya         = 'lainnya';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PersegiPanjang  => 'Persegi Panjang',
            self::Persegi         => 'Persegi',
            self::Trapesium       => 'Trapesium',
            self::Segitiga        => 'Segitiga',
            self::Lingkaran       => 'Lingkaran',
            self::TidakBeraturan  => 'Tidak Beraturan',
            self::LetterL         => 'Letter L',
            self::Lainnya         => 'Lainnya',
        };
    }
}
