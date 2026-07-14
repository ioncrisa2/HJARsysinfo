<?php

namespace App\Exports\Sheets;

use App\Models\Pembanding;
use App\Support\Exports\PembandingExportColumnRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembandingQueryDataSheet extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** @param array<int, string> $columns */
    public function __construct(
        private readonly Builder $builder,
        private readonly array $columns,
        private readonly PembandingExportColumnRegistry $registry,
    ) {}

    public function query(): Builder
    {
        return clone $this->builder;
    }

    public function headings(): array
    {
        return $this->registry->headings($this->columns);
    }

    public function map($row): array
    {
        /** @var Pembanding $row */
        return $this->excelValues($this->registry->map($row, $this->columns, true));
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $definitions = $this->registry->columns();

        return collect($this->columns)->mapWithKeys(function (string $column, int $index) use ($definitions): array {
            $format = match ($definitions[$column]['type']) {
                'currency' => '#,##0',
                'decimal' => '#,##0.00',
                'coordinate' => '0.000000',
                'date' => 'yyyy-mm-dd',
                'datetime' => 'yyyy-mm-dd hh:mm:ss',
                default => NumberFormat::FORMAT_GENERAL,
            };

            return [Coordinate::stringFromColumnIndex($index + 1) => $format];
        })->all();
    }

    private function excelValues(array $values): array
    {
        $definitions = $this->registry->columns();

        return collect($values)->map(function (mixed $value, int $index) use ($definitions): mixed {
            $type = $definitions[$this->columns[$index]]['type'];
            if ($value && in_array($type, ['date', 'datetime'], true)) {
                return Date::dateTimeToExcel(Carbon::parse($value));
            }

            return $value;
        })->all();
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
            $event->sheet->freezePane('A2');
            $event->sheet->setAutoFilter("A1:{$highestColumn}1");
        }];
    }

    public function title(): string
    {
        return 'Data Pembanding';
    }
}
