<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum KondisiTanah: string implements HasLabel
{
    case Matang            = 'matang';
    case Rawa              = 'rawa';
    case Sawah             = 'sawah';
    case BelumDikembangkan = 'belum_dikembangkan';
    case Lainnya           = 'lainnya';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Matang            => 'Matang',
            self::Rawa              => 'Rawa',
            self::Sawah             => 'Sawah',
            self::BelumDikembangkan => 'Belum Dikembangkan',
            self::Lainnya           => 'Lainnya',
        };
    }
}
