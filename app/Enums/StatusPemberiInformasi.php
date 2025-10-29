<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatusPemberiInformasi: string implements HasLabel
{
    case AgenProperti     = 'agen_properti';
    case PemilikProperti  = 'pemilik_properti';
    case PihakKe3         = 'pihak_ke3';
    case Perantara        = 'perantara';
    case KeluargaPemilik  = 'keluarga_pemilik';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AgenProperti    => 'Agen Properti',
            self::PemilikProperti => 'Pemilik Properti',
            self::PihakKe3        => 'Pihak Ke 3',
            self::Perantara       => 'Perantara',
            self::KeluargaPemilik => 'Keluarga Pemilik',
        };
    }
}
