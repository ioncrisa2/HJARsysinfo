<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataPembandings extends ListRecords
{
    protected static string $resource = DataPembandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Baru')                 // ganti teks tombol
                ->icon('heroicon-o-plus')                    // tambahkan icon (heroicons)
                ->color('warning')                           // opsional: warna
                ->button()                                   // pastikan tampil sebagai button (bukan icon-only)
                ->modalHeading('Tambah Data Pembanding')     // opsional: judul modal
                ->modalSubmitActionLabel('Simpan'),
        ];
    }
}
