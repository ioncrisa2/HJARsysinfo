<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class DataLokasi extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Data Lokasi';
    protected static ?string $navigationGroup = 'Data';
    protected static ?int $navigationSort = 2;
}
