<?php

namespace App\Filament\Components;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Password;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword as BaseUpdatePassword;

class CustomUpdatePassword extends BaseUpdatePassword
{
    /**
     * Kita mendefinisikan ulang method form()
     * untuk menambahkan ->revealable()
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label(__('filament-breezy::default.fields.current_password'))
                    ->password()
                    ->revealable() // Bisa ditambahkan di sini juga jika mau
                    ->required()
                    ->rule('current_password'), // Aturan validasi bawaan Breezy

                TextInput::make('password')
                    ->label(__('filament-breezy::default.fields.new_password'))
                    ->password()
                    ->revealable() // <--- INI YANG ANDA INGINKAN
                    ->rules([
                        'required',
                        'string',
                        Password::min(8)
                            ->mixedCase() // Jika ingin ada huruf besar/kecil
                            ->numbers()   // Jika ingin ada angka
                            ->symbols(),  // Jika ingin ada simbol
                    ]) // Menggunakan rules dari config
                    ->confirmed(),

                TextInput::make('password_confirmation')
                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                    ->password()
                    ->revealable() // <--- INI YANG ANDA INGINKAN
                    ->required(),
            ])
            ->statePath('data');
    }

    // Kita tidak perlu membuat method submit()
    // karena kita sudah mewarisi fungsionalitasnya
    // dari BaseUpdatePassword
}
