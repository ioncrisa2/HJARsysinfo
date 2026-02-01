<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use Filament\Actions;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Filament\Infolists\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\DataPembandingResource;
use Filament\Infolists\Components\RepeatableEntry;
use Nben\FilamentRecordNav\Actions\NextRecordAction;
use Nben\FilamentRecordNav\Actions\PreviousRecordAction;
use Nben\FilamentRecordNav\Concerns\WithRecordNavigation;

class ViewDataPembanding extends ViewRecord
{
    use WithRecordNavigation;

    protected static string $resource = DataPembandingResource::class;
    protected static ?string $title = 'Detail Data Pembanding';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('Riwayat Perubahan')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('Riwayat Perubahan Data')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->slideOver()
                ->infolist([
                    RepeatableEntry::make('activities')
                        ->hiddenLabel()
                        ->contained(false)
                        ->schema([
                            Section::make()
                                ->compact()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('created_at')
                                            ->label('Waktu')
                                            ->dateTime('d M Y, H:i:s', timezone: 'Asia/Jakarta')
                                            ->icon('heroicon-m-calendar')
                                            ->color('gray'),
                                        TextEntry::make('causer.name')
                                            ->label('Oleh')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Sistem'),
                                        TextEntry::make('event')
                                            ->label('Aksi')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                'created' => 'success',
                                                'updated' => 'warning',
                                                'deleted' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                                    ]),

                                    ViewEntry::make('properties')
                                        ->label('Rincian Perubahan')
                                        ->view('infolists.components.activity-log-table')
                                        ->visible(fn($record) => $record->event === 'updated' && !empty($record->properties)),
                                ])
                        ]),
                ]),
            Actions\EditAction::make(),
            Actions\Action::make('map')
                ->label('Buka Peta')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->url(
                    fn() => $this->record->latitude && $this->record->longitude
                        ? "https://www.google.com/maps?q={$this->record->latitude},{$this->record->longitude}"
                        : null,
                    true
                )
                ->disabled(fn() => ! ($this->record->latitude && $this->record->longitude)),

            Actions\Action::make('deleteWithPin')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Hapus Data Pembanding')
                ->modalDescription('Masukkan PIN penghapusan. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Hapus')
                ->form([
                    TextInput::make('pin')
                        ->label('PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->numeric()
                        ->minLength(4)
                        ->maxLength(12),

                    Textarea::make('reason')
                        ->label('Alasan Penghapusan')
                        ->rows(3)
                        ->required()
                        ->maxLength(1000)
                ])
                ->action(function (array $data): void {
                    $pinHash = SystemSetting::getValue('deletion_pin_hash');

                    if (! $pinHash) {
                        Notification::make()
                            ->title('PIN penghapusan belum diset.')
                            ->body('Minta superadmin mengatur PIN terlebih dahulu.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! Hash::check($data['pin'], $pinHash)) {
                        Notification::make()
                            ->title('PIN salah')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->deleted_by_id = auth()->id();
                    $this->record->deleted_reason = $data['reason'] ?? null;
                    $this->record->save();
                    $this->record->delete();

                    Notification::make()
                        ->title('Data berhasil dihapus')
                        ->success()
                        ->send();

                    $this->redirect(DataPembandingResource::getUrl('index'));
                }),

            PreviousRecordAction::make()
                ->label('← Previous')
                ->color('secondary')
                ->size('sm'),

            NextRecordAction::make()
                ->label('Next →')
                ->color('secondary')
                ->size('sm'),
        ];
    }
}
