<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PosisiTanah: string implements HasLabel
{
    case KuldesakLot  = 'kuldesak_lot';
    case InteriorLot  = 'interior_lot';
    case TSectionLot  = 't_section_lot';
    case CornerLot    = 'corner_lot';
    case KeyLot       = 'key_lot';
    case FlagLot      = 'flag_lot';
    case TanpaAkses   = 'tanpa_akses';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::KuldesakLot => 'Ujung Jalan (Kuldesak Lot)',
            self::InteriorLot => 'Berada di Tengah (Interior Lot)',
            self::TSectionLot => 'Tusuk Sate (Tusuk Sate)',
            self::CornerLot   => 'Suduk / Hook (Corner Lot)',
            self::KeyLot      => 'Mengunci Lot Lain (Key Lot)',
            self::FlagLot     => 'Berbentu Seperti Bendera (Key Lot)',
            self::TanpaAkses  => 'Tanpa Akses Jalan (Helicopter)',
        };
    }
}
