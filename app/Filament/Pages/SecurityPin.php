<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class SecurityPin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Pengaturan'; // match your top menu label
    protected static ?string $navigationLabel = 'Security PIN';
    protected static ?string $title = 'Security PIN';

    protected static ?string $slug = 'security-pin';

    protected static string $view = 'filament.pages.security-pin';

    public ?string $new_pin = null;
    public ?string $confirm_pin = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Set Deletion PIN')
                ->description('PIN ini dipakai untuk konfirmasi penghapusan data. Disimpan dalam bentuk hash.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('new_pin')
                        ->label('PIN Baru')
                        ->password()
                        ->revealable()
                        ->required()
                        ->inputMode('numeric')
                        ->rule('regex:/^[0-9]{4,12}$/')
                        ->minLength(4)
                        ->maxLength(12)
                        ->helperText('Gunakan 4-12 digit angka.'),

                    Forms\Components\TextInput::make('confirm_pin')
                        ->label('Ulangi PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->inputMode('numeric')
                        ->rule('regex:/^[0-9]{4,12}$/')
                        ->same('new_pin'),
                ]),
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'new_pin' => ['required', 'digits_between:4,12'],
            'confirm_pin' => ['required', 'same:new_pin'],
        ]);

        SystemSetting::setValue('deletion_pin_hash', Hash::make($this->new_pin));

        $this->new_pin = null;
        $this->confirm_pin = null;

        Notification::make()
            ->title('PIN berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getForms(): array
    {
        return ['form'];
    }
}
