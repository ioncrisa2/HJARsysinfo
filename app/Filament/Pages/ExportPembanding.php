<?php

namespace App\Filament\Pages;

use App\Exports\PembandingSelectionExport;
use App\Models\Pembanding;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ExportPembanding extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Export Data Pembanding';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'export-pembanding';
    protected static ?string $title = 'Export Data Pembanding';
    protected static string $view = 'filament.pages.export-pembanding';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->baseQuery())
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nama_pemberi_informasi')->label('Nama')->searchable()->limit(30),
                TextColumn::make('alamat_data')->label('Alamat')->searchable()->limit(40),
                TextColumn::make('province.name')->label('Provinsi')->toggleable(),
                TextColumn::make('regency.name')->label('Kab/Kota')->toggleable(),
                TextColumn::make('jenisListing.name')->label('Jenis Listing')->toggleable(),
                TextColumn::make('jenisObjek.name')->label('Jenis Objek')->toggleable(),
                TextColumn::make('harga')->label('Harga')->money('idr', 0)->sortable(),
                TextColumn::make('tanggal_data')->label('Tanggal Data')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('province_id')
                    ->label('Provinsi')
                    ->relationship('province', 'name'),
                SelectFilter::make('regency_id')
                    ->label('Kab/Kota')
                    ->relationship('regency', 'name'),
                Filter::make('tanggal_data')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('tanggal_data', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('tanggal_data', '<=', $date));
                    }),
            ])
            ->bulkActions([
                BulkAction::make('export_excel')
                    ->label('Export ke Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->exportExcel($records)),

                BulkAction::make('export_pdf')
                    ->label('Export ke PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->exportPdf($records)),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped();
    }

    protected function baseQuery(): Builder
    {
        return Pembanding::query()->with([
            'province',
            'regency',
            'district',
            'village',
            'jenisListing',
            'jenisObjek',
            'statusPemberiInformasi',
            'bentukTanah',
            'dokumenTanah',
            'posisiTanah',
            'kondisiTanah',
            'topografiRef',
            'peruntukanRef',
        ]);
    }

    protected function exportExcel(Collection $records)
    {
        $filename = 'pembanding-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PembandingSelectionExport($records), $filename);
    }

    protected function exportPdf(Collection $records)
    {
        $filename = 'pembanding-' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('exports.pembanding-pdf', [
            'records' => $records,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }
}
