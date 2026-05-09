<?php

namespace App\Enums;

enum JenisListing: string
{
    case Penawaran         = 'penawaran';
    case Transaksi          = 'transaksi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Penawaran         => 'Penawaran',
            self::Transaksi         => 'Transaksi',
        };
    }
}
