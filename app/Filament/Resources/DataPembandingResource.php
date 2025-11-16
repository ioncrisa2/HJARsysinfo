<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Regency;
use App\Models\Village;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Enums\Topografi;
use App\Models\District;
use App\Models\Province;
use Filament\Forms\Form;
use App\Enums\JenisObjek;
use App\Enums\Peruntukan;
use App\Enums\BentukTanah;
use App\Enums\PosisiTanah;
use App\Models\Pembanding;
use Filament\Tables\Table;
use App\Enums\DokumenTanah;
use App\Enums\JenisListing;
use App\Enums\KondisiTanah;
use Filament\Support\RawJs;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use App\Enums\StatusPemberiInformasi;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use App\Filament\Resources\DataPembandingResource\Pages;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;
use Filament\Infolists\Components\Section as ComponentsSection;
use Tgeorgel\FilamentTableLayoutToggle\Table\TableLayoutToggle;

class DataPembandingResource extends Resource
{
    protected static ?string $model = Pembanding::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?string $navigationLabel = "Bank Data Pembanding";
    protected static ?string $pluralLabel = "Bank Data Pembanding";


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('created_by')
                    ->default(Auth::id()),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Informasi Umum')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Select::make('jenis_listing')
                                    ->label('Jenis Listing Properti')
                                    ->options(['penawaran' => 'Penawaran', 'transaksi' => 'Transaksi'])->required(),

                                Select::make('jenis_objek')
                                    ->label('Jenis Objek Properti')
                                    ->options(JenisObjek::class)->searchable()->required(),

                                TextInput::make('nama_pemberi_informasi')
                                    ->label('Nama Pemberi Informasi')
                                    ->required()
                                    ->maxLength(255),

                                PhoneInput::make('nomer_telepon_pemberi_informasi')
                                    ->label('Nomer Telepon Pemberi Informasi')
                                    ->defaultCountry('ID')
                                    ->displayNumberFormat(PhoneInputNumberType::NATIONAL),

                                Select::make('status_pemberi_informasi')
                                    ->label('Status Pemberi Informasi')
                                    ->options(StatusPemberiInformasi::class),

                                DatePicker::make('tanggal_data')
                                    ->label('Tanggal Data Pembanding diupload')
                                    ->default(now())
                                    ->required()
                                    ->default(now()),

                            ]),

                        Tabs\Tab::make('Detail Lokasi')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Textarea::make('alamat_data')
                                    ->label('Alamat Lengkap')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('Contoh: Jl. Merdeka No.10.'),


                                Select::make('province_id')
                                    ->label('Provinsi')
                                    ->options(fn() => Province::all()->pluck('name', 'id'))
                                    ->searchable()->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('regency_id', null);
                                        $set('district_id', null);
                                        $set('village_id', null);
                                    }),


                                Select::make('regency_id')
                                    ->label('Kabupaten / Kota')
                                    ->searchable()
                                    ->required()
                                    ->visible(fn(Get $get) => filled($get('province_id')))
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('district_id', null);
                                        $set('village_id', null);
                                    })->options(fn(Get $get) => Regency::where('province_id', $get('province_id'))->pluck('name', 'id')),

                                Select::make('district_id')
                                    ->label('Kecamatan')
                                    ->searchable()
                                    ->required()
                                    ->visible(fn(Get $get) => filled($get('regency_id')))
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('village_id', null))
                                    ->options(fn(Get $get) => District::where('regency_id', $get('regency_id'))->pluck('name', 'id')),

                                Select::make('village_id')
                                    ->label('Desa/Kelurahan')
                                    ->searchable()
                                    ->required()
                                    ->visible(fn(Get $get) => filled($get('district_id')))
                                    ->options(
                                        fn(Get $get) =>
                                        Village::where('district_id', $get('district_id'))
                                            ->pluck('name', 'id')
                                    ),


                                Fieldset::make('Koordinat Lokasi')
                                    ->schema([
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->numeric()
                                            ->required(),
                                    ]),

                            ]),

                        Tabs\Tab::make('Detail Data Pembanding')
                            ->icon('heroicon-o-document-text')
                            ->schema([

                                FileUpload::make('image')
                                    ->label('Foto Data Pembanding')
                                    ->image()
                                    ->disk('public')
                                    ->directory('foto_pembanding')
                                    ->maxSize(15360) // 15MB
                                    ->required()
                                    ->helperText('Unggah foto properti pembanding (maks 5MB).'),

                                Forms\Components\TextInput::make('luas_tanah')
                                    ->label('Luas Tanah')
                                    ->suffix('m²')
                                    ->reactive()
                                    ->placeholder('Contoh: 120')
                                    ->helperText('Masukkan luas tanah dalam meter persegi'),

                                Forms\Components\TextInput::make('luas_bangunan')
                                    ->label('Luas Bangunan')
                                    ->suffix('m²')
                                    ->reactive()
                                    ->placeholder('Contoh: 120')
                                    ->helperText('Masukkan luas bangunan bila property memiliki bangunan'),

                                TextInput::make('tahun_bangun')
                                    ->label('Tahun Bangun')
                                    ->maxValue((int) now()->format('Y'))
                                    ->helperText('Masukkan tahun 4 digit, misal: 2010'),

                                Select::make('bentuk_tanah')
                                    ->label('Bentuk Tanah')
                                    ->options(BentukTanah::class)->searchable()->placeholder('Pilih Bentuk Tanah')
                                    ->required()->helperText('Pilih bentuk tanah properti'),

                                Select::make('dokumen_tanah')
                                    ->label('Dokumen Tanah')
                                    ->options(DokumenTanah::class)->searchable()->placeholder('Pilih Dokumen Tanah')
                                    ->required()->helperText('Pilih jenis dokumen legalitas tanah'),

                                Select::make('posisi_tanah')
                                    ->label('Posisi Letak Tanah')
                                    ->options(PosisiTanah::class)->searchable(),

                                Select::make('kondisi_tanah')
                                    ->label('Kondisi Lahan / Tanah')
                                    ->options(KondisiTanah::class),

                                Select::make('topografi')
                                    ->label('Pilih Topografi tanah Aset')
                                    ->options(Topografi::class),

                                Forms\Components\TextInput::make('lebar_depan')
                                    ->label('Lebar Depan Tanah')
                                    ->suffix('m')
                                    ->reactive()
                                    ->placeholder('Contoh: 120')
                                    ->helperText('Masukkan panjang lebar depan tanah dalam meter'),

                                Forms\Components\TextInput::make('lebar_jalan')
                                    ->label('Lebar Akses Jalan Depan Tanah')
                                    ->suffix('m')
                                    ->reactive()
                                    ->placeholder('Contoh: 120')
                                    ->helperText('Masukkan lebar akses jalan depan tanah dalam meter'),

                                Select::make('peruntukan')
                                    ->label('Pilih Peruntukan Lahan/Bangunan')
                                    ->options(Peruntukan::class)->searchable()->placeholder('Pilih Peruntukan Lahan/Bangunan'),

                                TextInput::make('rasio_tapak')
                                    ->label('Site Coverage / Plot Ratio')
                                    ->placeholder('KDB/KLB/TL')
                                    ->helperText('Masukkan rasio tapak properti (Floor Area Ratio) jika diketahui'),

                                TextInput::make('harga')
                                    ->label('Harga Estimasi')
                                    ->prefix('Rp')
                                    ->numeric() // Validasi input harus angka
                                    ->minValue(0)

                                    // 1. TAMPILAN: Kasih titik otomatis (Visual saja)
                                    ->mask(RawJs::make(<<<'JS'
                                        $money($input, ',', '.', 0)
                                    JS))

                                    // 2. PENYIMPANAN: Hapus titik sebelum masuk ke BIGINT
                                    ->stripCharacters('.')

                                    ->required(),
                            ]),

                        Tabs\Tab::make('Catatan Tambahan')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Textarea::make('catatan')
                                    ->label('Catatan Tambahan')
                                    ->rows(5)
                                    ->maxLength(1000)
                                    ->placeholder('Masukkan catatan tambahan di sini...'),
                            ])
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([

                    ImageColumn::make('image')
                        ->height('200px')
                        ->width('100%')
                        ->extraImgAttributes([
                            'class' => 'object-cover w-full rounded-t-lg',
                            'style' => 'border-bottom: 1px solid #f3f4f6;',
                        ])->defaultImageUrl('https://placehold.co/600x400?text=No+Image'),

                    Stack::make([

                        TextColumn::make('alamat_singkat')
                            ->weight('bold')
                            ->size('lg')
                            ->limit(60)
                            ->tooltip(fn($record) => $record->alamat_data)
                            ->extraAttributes(['class' => 'mb-1 leading-tight']),

                        TextColumn::make('harga')
                            ->money('IDR', divideBy: 1000)
                            ->weight('black') // Sangat tebal
                            ->color('primary')
                            ->size('xl') // Font paling besar
                            ->extraAttributes(['class' => 'mb-2']),

                        TextColumn::make('luas_tanah')
                            ->formatStateUsing(fn($state, Pembanding $record) => "LT: {$state} m² • {$record->dokumen_tanah->getLabel()}")
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-m-map'),

                        TextColumn::make('jenis_listing')
                            ->color('white')
                            ->size('sm')
                            ->icon('heroicon-m-newspaper')
                            ->formatStateUsing(fn($state, Pembanding $record)=> "{$state->getLabel()} - {$record->jenis_objek->getLabel()}")

                    ])->extraAttributes(['class' => 'p-4 space-y-2'])
                ])

            ])
            ->filters([
                Filter::make('lokasi')
                    ->form([
                        //filter berdasarkan lokasi
                        Select::make('province_id')
                            ->label('Provinsi')
                            ->options(fn() => \App\Models\Province::query()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => [
                                $set('regency_id', null),
                                $set('district_id', null),
                                $set('village_id', null),
                            ]),

                        Select::make('regency_id')
                            ->label('Kabupaten/Kota')
                            ->options(fn(Forms\Get $get) => $get('province_id')
                                ? \App\Models\Regency::where('province_id', $get('province_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => [
                                $set('district_id', null),
                                $set('village_id', null),
                            ])
                            ->visible(fn(Forms\Get $get) => filled($get('province_id'))),

                        Select::make('district_id')
                            ->label('Kecamatan')
                            ->options(fn(Forms\Get $get) => $get('regency_id')
                                ? \App\Models\District::where('regency_id', $get('regency_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('village_id', null))
                            ->visible(fn(Forms\Get $get) => filled($get('regency_id'))),

                        Select::make('village_id')
                            ->label('Desa/Kelurahan')
                            ->options(fn(Forms\Get $get) => $get('district_id')
                                ? \App\Models\Village::where('district_id', $get('district_id'))->pluck('name', 'id')
                                : collect())
                            ->searchable()
                            ->visible(fn(Forms\Get $get) => filled($get('district_id'))),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['province_id'] ?? null, fn($q, $v) => $q->where('province_id', $v))
                            ->when($data['regency_id'] ?? null,  fn($q, $v) => $q->where('regency_id', $v))
                            ->when($data['district_id'] ?? null, fn($q, $v) => $q->where('district_id', $v))
                            ->when($data['village_id'] ?? null,  fn($q, $v) => $q->where('village_id', $v));
                    })
                    ->indicateUsing(function (array $data) {
                        $chips = [];
                        if ($id = $data['province_id'] ?? null) {
                            $name = \App\Models\Province::find($id)?->name;
                            $chips[] = "Provinsi: {$name}";
                        }
                        if ($id = $data['regency_id'] ?? null) {
                            $name = \App\Models\Regency::find($id)?->name;
                            $chips[] = "Kab/Kota: {$name}";
                        }
                        if ($id = $data['district_id'] ?? null) {
                            $name = \App\Models\District::find($id)?->name;
                            $chips[] = "Kecamatan: {$name}";
                        }
                        if ($id = $data['village_id'] ?? null) {
                            $name = \App\Models\Village::find($id)?->name;
                            $chips[] = "Desa: {$name}";
                        }
                        return $chips;
                    }),

                //filter berdasarkan nama jalan
                Filter::make('nama_jalan')
                    ->form([
                        TextInput::make('q')
                            ->label('Nama Jalan')
                            ->placeholder('mis. Jl. Merdeka')
                            ->autocomplete(false),
                    ])
                    ->query(
                        fn($query, array $data) =>
                        $query->when(
                            filled($data['q'] ?? null),
                            fn($q) => $q->where('alamat_data', 'like', '%' . $data['q'] . '%')
                        )
                    )
                    ->indicateUsing(
                        fn(array $data) =>
                        filled($data['q'] ?? null) ? ["Jalan: {$data['q']}"] : []
                    ),

                //filter berdasarkan tanggal data
                Filter::make('tanggal_data')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date) => $query->whereDate('tanggal_data', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date) => $query->whereDate('tanggal_data', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari Tanggal: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->toFormattedDateString();
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai Tanggal: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                SelectFilter::make('jenis_objek')
                    ->label('Jenis Objek')
                    ->options(JenisObjek::class)
                    ->native(false)
                    ->searchable()

            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->persistColumnSearchesInSession()
            ->actions([
                Tables\Actions\Action::make('map')
                    ->label('')
                    ->color('warning')
                    ->icon('heroicon-o-map')
                    ->tooltip('Buka Lokasi di Peta Maps')
                    ->visible(fn(Pembanding $r) => $r->latitude && $r->longitude)
                    ->url(fn(Pembanding $r) => 'https://www.google.com/maps?q=' . $r->latitude . ',' . $r->longitude, true),
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Lihat Detail Data')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->color('white')
                    ->tooltip('Edit Data'),
                // Tables\Actions\DeleteAction::make()
                //     ->label('')
                //     ->tooltip('Hapus Data')
                //     ->icon('heroicon-o-trash')
                //     ->color('danger'),
                // Tables\Actions\ForceDeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])->paginated(['5', '10', '25', '50', '100', '250', 'all']);
    }


    public static function infolist(Infolist $infolist): Infolist
    {

        $latitude = $infolist->getRecord()->latitude;
        $longitude = $infolist->getRecord()->longitude;

        return $infolist->schema([

            Grid::make(12)->schema([
                ComponentsSection::make('Detail Data')
                    ->schema([

                        Grid::make(2)->schema([
                            TextEntry::make('jenis_objek')
                                ->label('Jenis Objek')
                                ->badge()
                                ->icon('heroicon-o-home-modern')
                                ->color('primary')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('harga')
                                ->label('Harga')
                                ->money('IDR', divideBy: 100)
                                ->badge()
                                ->icon('heroicon-o-banknotes')
                                ->color('success')
                                ->weight('bold'),
                        ]),

                        TextEntry::make('alamat_lengkap')
                            ->label('Alamat Lengkap')
                            ->html()
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextEntry::make('luas_tanah')
                                ->label('Luas Tanah')
                                ->suffix(' m²'),

                            TextEntry::make('luas_bangunan')
                                ->label('Luas Bangunan')
                                ->formatStateUsing(fn(?string $state) => (is_null($state) || $state === '0' || $state === 0) ? '-' : "{$state} m²"),

                            TextEntry::make('tahun_bangun')
                                ->label('Tahun Bangun')
                                ->placeholder('—'),

                            TextEntry::make('bentuk_tanah')
                                ->label('Bentuk Tanah'),

                            TextEntry::make('dokumen_tanah')
                                ->label('Dokumen Legalitas Tanah'),

                            TextEntry::make('posisi_tanah')
                                ->label('Letak Posisi Tanah'),

                            TextEntry::make('kondisi_tanah')
                                ->label('Kondisi Tanah'),

                            TextEntry::make('topografi')
                                ->label('Topografi Tanah'),

                            TextEntry::make('lebar_depan')
                                ->label('Lebar Depan Tanah')
                                ->suffix(' m'),

                            TextEntry::make('lebar_jalan')
                                ->label('Lebar Akses Jalan Depan Aset')
                                ->suffix(' m'),

                            TextEntry::make('peruntukan')
                                ->label('Pengunaan Aset Saat ini'),

                            TextEntry::make('rasio_tapak')
                                ->label('Rasio Lahan'),

                        ]),
                        TextEntry::make('catatan')
                            ->label('Catatan Tambahan')
                            ->html()
                            ->placeholder('—'),
                    ])
                    ->columnSpan(8),

                // RIGHT: gambar & status
                ComponentsSection::make('Pemberi Informasi')
                    ->schema([

                        ImageEntry::make('image')
                            ->label('Foto')
                            ->disk('public')
                            ->height(280)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover w-full h-[220px]']),

                        TextEntry::make('tanggal_data')
                            ->label('Tanggal Data')
                            ->date('l, d F Y'),

                        TextEntry::make('nama_pemberi_info')
                            ->label('Pemberi Informasi'),

                        TextEntry::make('nomer_info')
                            ->label('Nomer Telepon Pemberi Informasi'),

                        MapEntry::make('Map Preview')
                            ->defaultLocation(
                                latitude: $latitude,
                                longitude: $longitude
                            )
                            ->draggable(false)
                            ->showMarker(true)
                            ->markerColor("#22c55eff")
                            ->showZoomControl(true)
                            ->tilesUrl("https://api.maptiler.com/tiles/satellite-v2/{z}/{x}/{y}.jpg?key=tsNmu1udsggoxQXYTlrP")
                            ->extraStyles([
                                'border-radius: 20px',
                                'z-index: 1 !important',
                                'position: relative',
                            ]),

                        TextEntry::make('koordinat_string')
                            ->label('Koordinat Lokasi'),

                    ])
                    ->columnSpan(4),
            ]),

        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataPembandings::route('/'),
            'create' => Pages\CreateDataPembanding::route('/create'),
            'edit' => Pages\EditDataPembanding::route('/{record}/edit'),
            'view' => Pages\ViewDataPembanding::route('/{record}'),
        ];
    }
}
