<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MasterData extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Master Data';
    protected static ?string $navigationGroup = 'Data';
    protected static ?int $navigationSort = 1;
}
