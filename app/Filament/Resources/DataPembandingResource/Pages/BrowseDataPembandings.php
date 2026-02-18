<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use App\Models\District;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrowseDataPembandings extends Page implements HasTable, HasForms
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = DataPembandingResource::class;

    protected static string $view = 'filament.resources.data-pembanding-resource.pages.browse-data-pembandings';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill($this->defaultFilters());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filter Data')
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('Provinsi')
                            ->options(fn () => Province::query()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => [
                                $set('regency_id', null),
                                $set('district_id', null),
                                $set('village_id', null),
                            ]),

                        Forms\Components\Select::make('regency_id')
                            ->label('Kabupaten/Kota')
                            ->options(fn (Get $get) => $get('province_id')
                                ? Regency::query()->where('province_id', $get('province_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => [
                                $set('district_id', null),
                                $set('village_id', null),
                            ])
                            ->visible(fn (Get $get) => filled($get('province_id'))),

                        Forms\Components\Select::make('district_id')
                            ->label('Kecamatan')
                            ->options(fn (Get $get) => $get('regency_id')
                                ? District::query()->where('regency_id', $get('regency_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('village_id', null))
                            ->visible(fn (Get $get) => filled($get('regency_id'))),

                        Forms\Components\Select::make('village_id')
                            ->label('Desa/Kelurahan')
                            ->options(fn (Get $get) => $get('district_id')
                                ? Village::query()->where('district_id', $get('district_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->visible(fn (Get $get) => filled($get('district_id'))),

                        Forms\Components\TextInput::make('q')
                            ->label('Nama Jalan')
                            ->placeholder('mis. Jl. Merdeka')
                            ->autocomplete(false)
                            ->live(debounce: 400),

                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->live(),

                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->live(),

                        Forms\Components\Select::make('jenis_listing_id')
                            ->label('Jenis Listing')
                            ->options(fn () => JenisListing::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Select::make('jenis_objek_id')
                            ->label('Jenis Objek')
                            ->options(fn () => JenisObjek::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
            ])
            ->statePath('filters');
    }

    public function table(Table $table): Table
    {
        return DataPembandingResource::table($table)
            ->query(fn (): Builder => $this->buildFilteredQuery())
            ->filters([]);
    }

    public function updatedFilters(mixed $value = null, ?string $key = null): void
    {
        if ($key === 'q') {
            $this->filters['q'] = trim((string) ($this->filters['q'] ?? ''));

            if ($this->filters['q'] === '') {
                $this->filters['q'] = null;
            }
        }

        $this->flushCachedTableRecords();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filters = $this->defaultFilters();
        $this->form->fill($this->filters);

        $this->flushCachedTableRecords();
        $this->resetPage();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Tambah Data Baru')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->button()
                ->url(DataPembandingResource::getUrl('create')),
        ];
    }

    protected function buildFilteredQuery(): Builder
    {
        $filters = $this->filters;
        $search = trim((string) ($filters['q'] ?? ''));

        return DataPembandingResource::getEloquentQuery()
            ->when($filters['province_id'] ?? null, fn (Builder $query, $value) => $query->where('province_id', $value))
            ->when($filters['regency_id'] ?? null, fn (Builder $query, $value) => $query->where('regency_id', $value))
            ->when($filters['district_id'] ?? null, fn (Builder $query, $value) => $query->where('district_id', $value))
            ->when($filters['village_id'] ?? null, fn (Builder $query, $value) => $query->where('village_id', $value))
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where('alamat_data', 'like', '%' . $search . '%')
            )
            ->when(
                $filters['dari_tanggal'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('tanggal_data', '>=', $date),
            )
            ->when(
                $filters['sampai_tanggal'] ?? null,
                fn (Builder $query, $date) => $query->whereDate('tanggal_data', '<=', $date),
            )
            ->when(
                $filters['jenis_listing_id'] ?? null,
                fn (Builder $query, $value) => $query->where('jenis_listing_id', $value),
            )
            ->when(
                $filters['jenis_objek_id'] ?? null,
                fn (Builder $query, $value) => $query->where('jenis_objek_id', $value),
            );
    }

    protected function defaultFilters(): array
    {
        return [
            'province_id' => null,
            'regency_id' => null,
            'district_id' => null,
            'village_id' => null,
            'q' => null,
            'dari_tanggal' => null,
            'sampai_tanggal' => null,
            'jenis_listing_id' => null,
            'jenis_objek_id' => null,
        ];
    }
}
