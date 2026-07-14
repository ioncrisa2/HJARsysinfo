<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembandingMetadataSheet implements FromCollection, ShouldAutoSize, WithStyles, WithTitle
{
    /** @param array<string, mixed> $metadata */
    public function __construct(private readonly array $metadata) {}

    public function collection(): Collection
    {
        return collect($this->metadata)->map(fn (mixed $value, string $label): array => [
            $label,
            is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value,
        ])->values();
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:A'.$sheet->getHighestRow())->getFont()->setBold(true);
        $sheet->getStyle('A1:A'.$sheet->getHighestRow())->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        return [];
    }

    public function title(): string
    {
        return 'Metadata';
    }
}
