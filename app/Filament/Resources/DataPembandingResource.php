<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Regency;
use App\Models\Village;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\District;
use App\Models\Province;
use Filament\Forms\Form;
use App\Models\Pembanding;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use App\Filament\Resources\DataPembandingResource\Pages;
use Filament\Infolists\Components\Section as ComponentsSection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DataPembandingResource extends Resource
{
    protected static ?string $model = Pembanding::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = "Data Pembanding";
    protected static ?string $pluralLabel = "Daftar Bank Data";


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

                                Fieldset::make('Jenis Properti')
                                    ->schema([
                                        Select::make('jenis_listing_id')
                                            ->label('Jenis Listing')
                                            ->relationship('jenisListing', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->helperText('Contoh: Penawaran, Transaksi'),

                                        Select::make('jenis_objek_id')
                                            ->label('Jenis Objek')
                                            ->relationship('jenisObjek', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->helperText('Contoh: Tanah, Ruko, Rumah'),
                                    ])
                                    ->columns(2),

                                Fieldset::make('Data Pemberi Informasi')
                                    ->schema([
                                        TextInput::make('nama_pemberi_informasi')
                                            ->label('Nama')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),

                                        PhoneInput::make('nomer_telepon_pemberi_informasi')
                                            ->label('Nomor Telepon')
                                            ->defaultCountry('ID')
                                            ->displayNumberFormat(PhoneInputNumberType::NATIONAL)
                                            ->columnSpan(1),

                                        Select::make('status_pemberi_informasi_id')
                                            ->label('Status')
                                            ->relationship('statusPemberiInformasi', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Contoh: Pemilik, Agen, Broker')
                                            ->columnSpan(1),

                                        DatePicker::make('tanggal_data')
                                            ->label('Tanggal Data')
                                            ->default(now())
                                            ->required()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Detail Lokasi')
                            ->icon('heroicon-o-map-pin')
                            ->schema([

                                Textarea::make('alamat_data')
                                    ->label('Alamat Lengkap')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('Contoh: Jl. Merdeka No.10')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Fieldset::make('Wilayah')
                                    ->schema([
                                        Select::make('province_id')
                                            ->label('Provinsi')
                                            ->options(fn() => Province::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
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
                                            })
                                            ->options(fn(Get $get) => Regency::where('province_id', $get('province_id'))->pluck('name', 'id')),

                                        Select::make('district_id')
                                            ->label('Kecamatan')
                                            ->searchable()
                                            ->required()
                                            ->visible(fn(Get $get) => filled($get('regency_id')))
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('village_id', null))
                                            ->options(fn(Get $get) => District::where('regency_id', $get('regency_id'))->pluck('name', 'id')),

                                        Select::make('village_id')
                                            ->label('Desa / Kelurahan')
                                            ->searchable()
                                            ->required()
                                            ->visible(fn(Get $get) => filled($get('district_id')))
                                            ->options(fn(Get $get) => Village::where('district_id', $get('district_id'))->pluck('name', 'id')),
                                    ])
                                    ->columns(2),

                                Fieldset::make('Koordinat GPS')
                                    ->schema([
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('Contoh: -6.200000'),

                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('Contoh: 106.816666'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Detail Properti')
                            ->icon('heroicon-o-home-modern')
                            ->schema([

                                // ── Foto ───────────────────────────
                                FileUpload::make('image')
                                    ->label('Foto Properti')
                                    ->image()
                                    ->disk('public')
                                    ->directory('foto_pembanding')
                                    ->getUploadedFileNameForStorageUsing(
                                        function (TemporaryUploadedFile $file): string {
                                            return strtolower(Str::random(40)) . '.' . $file->getClientOriginalExtension();
                                        }
                                    )
                                    ->maxSize(15360)
                                    ->required()
                                    ->helperText('Unggah foto properti (maks 15MB)')
                                    ->columnSpanFull(),

                                Fieldset::make('Ukuran')
                                    ->schema([
                                        Forms\Components\TextInput::make('luas_tanah')
                                            ->label('Luas Tanah')
                                            ->suffix('m²')
                                            ->required()
                                            ->reactive()
                                            ->placeholder('Contoh: 120')
                                            ->helperText('Luas tanah dalam m²'),

                                        Forms\Components\TextInput::make('luas_bangunan')
                                            ->label('Luas Bangunan')
                                            ->suffix('m²')
                                            ->reactive()
                                            ->placeholder('Contoh: 80')
                                            ->helperText('Kosongkan jika tidak ada bangunan'),

                                        Forms\Components\TextInput::make('lebar_depan')
                                            ->label('Lebar Depan Tanah')
                                            ->suffix('m')
                                            ->reactive()
                                            ->required()
                                            ->placeholder('Contoh: 8'),

                                        Forms\Components\TextInput::make('lebar_jalan')
                                            ->label('Lebar Akses Jalan')
                                            ->suffix('m')
                                            ->reactive()
                                            ->required()
                                            ->placeholder('Contoh: 6'),

                                        TextInput::make('tahun_bangun')
                                            ->label('Tahun Bangun')
                                            ->maxValue((int) now()->format('Y'))
                                            ->placeholder('Contoh: 2010')
                                            ->helperText('Kosongkan jika tanah kosong'),

                                        TextInput::make('rasio_tapak')
                                            ->label('Site Coverage / Rasio Tapak')
                                            ->placeholder('KDB/KLB/TL')
                                            ->helperText('Floor Area Ratio jika diketahui'),
                                    ])
                                    ->columns(3),

                                Fieldset::make('Karakteristik Tanah')
                                    ->schema([
                                        Select::make('bentuk_tanah_id')
                                            ->label('Bentuk Tanah')
                                            ->relationship('bentukTanah', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('posisi_tanah_id')
                                            ->label('Posisi Letak')
                                            ->relationship('posisiTanah', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('kondisi_tanah_id')
                                            ->label('Kondisi Lahan')
                                            ->relationship('kondisiTanah', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('topografi_id')
                                            ->label('Topografi')
                                            ->relationship('topografiRef', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Fieldset::make('Legalitas & Peruntukan')
                                    ->schema([
                                        Select::make('dokumen_tanah_id')
                                            ->label('Dokumen Tanah')
                                            ->relationship('dokumenTanah', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->helperText('Jenis legalitas tanah'),

                                        Select::make('peruntukan_id')
                                            ->label('Peruntukan Lahan')
                                            ->relationship('peruntukanRef', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Pilih peruntukan'),
                                    ])
                                    ->columns(2),

                                Fieldset::make('Harga')
                                    ->schema([
                                        TextInput::make('harga')
                                            ->label('Estimasi Harga')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->minValue(0)
                                            ->mask(RawJs::make(<<<'JS'
                                                $money($input, ',', '.', 0)
                                            JS))
                                            ->stripCharacters('.')
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Catatan')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Textarea::make('catatan')
                                    ->label('Catatan Tambahan')
                                    ->rows(6)
                                    ->maxLength(1000)
                                    ->placeholder('Masukkan catatan tambahan, kondisi khusus, atau informasi lain yang relevan...')
                                    ->helperText('Opsional — maksimal 1000 karakter')
                                    ->columnSpanFull(),
                            ]),

                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString('tab'),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->width(72)
                    ->height(54)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->defaultImageUrl('https://placehold.co/72x54?text=No+Image'),

                TextColumn::make('alamat_singkat')
                    ->label('Alamat')
                    ->weight('bold')
                    ->url(fn(Pembanding $record): string => static::getUrl('view', ['record' => $record]))
                    ->description(
                        fn(Pembanding $record) => ($record->village?->name  ? $record->village->name  . ', ' : '') .
                            ($record->district?->name ? $record->district->name . ', ' : '') .
                            ($record->regency?->name  ?? '')
                    )
                    ->searchable(
                        query: fn(Builder $query, string $search) =>
                        $query->where('alamat_data', 'like', "%{$search}%")
                    )
                    ->limit(50)
                    ->grow(),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->weight('bold')
                    ->color('warning')
                    ->formatStateUsing(function ($state) {
                        $value = (float) ($state ?? 0);
                        if ($value >= 1_000_000_000) {
                            $m = floor(($value / 1_000_000_000) * 100) / 100;
                            return 'Rp ' . rtrim(rtrim(number_format($m, 2, '.', ''), '0'), '.') . ' M';
                        }
                        if ($value >= 1_000_000) {
                            $j = $value / 1_000_000;
                            if ($j >= 100) return 'Rp ' . (int) floor($j) . ' Juta';
                            $j = floor($j * 10) / 10;
                            return 'Rp ' . rtrim(rtrim(number_format($j, 1, '.', ''), '0'), '.') . ' Juta';
                        }
                        return 'Rp ' . number_format((int) $value, 0, ',', '.');
                    })
                    ->width('120px'),

                TextColumn::make('luas_tanah')
                    ->label('Luas')
                    ->formatStateUsing(
                        fn($state) =>
                        is_numeric($state)
                            ? ((float)$state >= 10000
                                ? number_format((float)$state / 10000, 2, ',', '.') . ' ha'
                                : number_format((float)$state, 0, ',', '.') . ' m²')
                            : '-'
                    )
                    ->width('90px'),

                // Listing + Objek merged into one column
                TextColumn::make('jenisListing.name')
                    ->label('Tipe')
                    ->formatStateUsing(
                        fn($state, Pembanding $record) => ($state ?? '-') . ' · ' . ($record->jenisObjek?->name ?? '-')
                    )
                    ->badge()
                    ->color('info')
                    ->width('170px'),

                // Tanggal — hidden (toggleable if needed)
                // Dokumen — hidden (visible in detail view)
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

                SelectFilter::make('jenis_listing_id')
                    ->label('Jenis Listing')
                    ->relationship('jenisListing', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('jenis_objek_id')
                    ->label('Jenis Objek')
                    ->relationship('jenisObjek', 'name')
                    ->searchable()
                    ->preload(),

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('map')
                        ->label('Lihat di Maps')
                        ->color('warning')
                        ->icon('heroicon-o-map')
                        ->visible(fn(Pembanding $r) => $r->latitude && $r->longitude)
                        ->url(
                            fn(Pembanding $r) =>
                            'https://www.google.com/maps?q=' . $r->latitude . ',' . $r->longitude,
                            true
                        ),

                    Tables\Actions\ViewAction::make()
                        ->label('Detail Data')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('info')
                        ->url(fn(Pembanding $record): string => static::getUrl('view', ['record' => $record])),

                    Tables\Actions\EditAction::make()
                        ->label('Edit Data')
                        ->icon('heroicon-o-pencil-square')
                        ->color('danger')
                        ->url(fn(Pembanding $record): string => static::getUrl('edit', ['record' => $record])),
                ])
                    ->tooltip('Aksi')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->color('gray')
                    ->size(\Filament\Support\Enums\ActionSize::Small),
            ])
            ->actionsColumnLabel('') // no column heading for the actions column
            ->paginated(['10', '25', '50', '100', '250', 'all']);
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
                            TextEntry::make('jenisObjek.name')
                                ->label('Jenis Objek')
                                ->badge()
                                ->icon('heroicon-o-home-modern')
                                ->color('primary')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('harga')
                                ->label('Harga')
                                ->money('IDR')
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
                                ->placeholder('—')
                                ->formatStateUsing(fn(?string $state) => (is_null($state) || $state === '0' || $state === 0) ? '-' : "{$state} m²"),

                            TextEntry::make('tahun_bangun')
                                ->label('Tahun Bangun')
                                ->placeholder('—'),

                            TextEntry::make('bentukTanah.name')->label('Bentuk Tanah'),
                            TextEntry::make('dokumenTanah.name')->label('Dokumen Legalitas Tanah'),
                            TextEntry::make('posisiTanah.name')->label('Letak Posisi Tanah'),
                            TextEntry::make('kondisiTanah.name')->label('Kondisi Tanah'),
                            TextEntry::make('topografiRef.name')->label('Topografi Tanah'),

                            TextEntry::make('lebar_depan')
                                ->label('Lebar Depan Tanah')
                                ->suffix(' m'),

                            TextEntry::make('lebar_jalan')
                                ->label('Lebar Akses Jalan Depan Aset')
                                ->suffix(' m'),

                            TextEntry::make('peruntukanRef.name')->label('Penggunaan Aset Saat ini'),

                            TextEntry::make('rasio_tapak')
                                ->label('Rasio Lahan')
                                ->placeholder('—'),

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
                            ->disk('public')
                            ->label('Foto')
                            ->height(280)
                            ->extraImgAttributes([
                                'class' => 'rounded-lg object-cover w-full h-[220px]'
                            ]),

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'jenisListing',
            'jenisObjek',
            'statusPemberiInformasi',
            'bentukTanah',
            'dokumenTanah',
            'posisiTanah',
            'kondisiTanah',
            'topografiRef',
            'peruntukanRef',
            'province',
            'regency',
            'district',
            'village',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\BrowseDataPembandings::route('/'),
            'create' => Pages\CreateDataPembanding::route('/create'),
            'edit' => Pages\EditDataPembanding::route('/{record}/edit'),
            'view' => Pages\ViewDataPembanding::route('/{record}'),
        ];
    }
}
