<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisListing: string implements HasLabel
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
