<?php

namespace App\Enums;

enum KondisiTanah: string
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
