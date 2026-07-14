<?php

namespace App\Exports;

use App\Exports\Sheets\PembandingMetadataSheet;
use App\Exports\Sheets\PembandingQueryDataSheet;
use App\Support\Exports\PembandingExportColumnRegistry;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PembandingQueryExport implements WithMultipleSheets
{
    /** @param array<int, string> $columns @param array<string, mixed> $metadata */
    public function __construct(
        private readonly Builder $query,
        private readonly array $columns,
        private readonly array $metadata,
        private readonly PembandingExportColumnRegistry $registry,
    ) {}

    public function sheets(): array
    {
        return [
            new PembandingQueryDataSheet($this->query, $this->columns, $this->registry),
            new PembandingMetadataSheet($this->metadata),
        ];
    }
}
