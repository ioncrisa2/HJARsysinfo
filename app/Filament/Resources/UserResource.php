<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $pluralLabel     = 'Users';

    public static function form(Form $form): Form
    {
        $guard = config('filament.auth.guard', 'web');

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation) => $operation === 'create'),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple() // <-- Tetap bisa pilih banyak role
                    ->preload()  // <-- Memuat role saat halaman dibuka
                    ->searchable() // <-- Memudahkan pencarian jika role banyak
                    ->helperText('Pilih satu atau lebih role untuk user ini.'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Roles')
                    ->colors(['primary'])
                    ->separator(', ')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('deactivated_at')
                    ->label('Status')
                    ->icon(fn($record) => $record->deactivated_at ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->deactivated_at ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
                TernaryFilter::make('deactivated_at')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Deactivated') // User yang tidak aktif
                    ->falseLabel('Active') // User yang aktif
                    ->queries(
                        true: fn(Builder $query) => $query->inactive(), // Menggunakan scope inactive()
                        false: fn(Builder $query) => $query->active(),   // Menggunakan scope active()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation() // Tampilkan modal konfirmasi
                    ->action(function (User $record) {
                        $record->deactivated_at = now();
                        $record->save();
                        Notification::make()
                            ->title('User Deactivated')
                            ->success()
                            ->send();
                    })
                    // Hanya tampilkan jika user sedang aktif (deactivated_at == null)
                    ->visible(fn(User $record): bool => is_null($record->deactivated_at)),

                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->deactivated_at = null;
                        $record->save();
                        Notification::make()
                            ->title('User Activated')
                            ->success()
                            ->send();
                    })
                    // Hanya tampilkan jika user sedang tidak aktif (deactivated_at != null)
                    ->visible(fn(User $record): bool => !is_null($record->deactivated_at)),
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
