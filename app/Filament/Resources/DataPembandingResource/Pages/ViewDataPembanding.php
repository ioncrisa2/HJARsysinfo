<?php

namespace App\Filament\Resources\DataPembandingResource\Pages;

use App\Filament\Resources\DataPembandingResource;
use App\Models\SystemSetting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Hash;

class ViewDataPembanding extends ViewRecord
{
    protected static string $resource = DataPembandingResource::class;

    protected static string $view = 'filament.resources.data-pembanding-resource.pages.view-data-pembanding';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('History Perubahan')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('History Perubahan Data')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn () => view(
                    'filament.resources.data-pembanding-resource.partials.change-history',
                    [
                        'activities' => Activity::query()
                            ->where('subject_type', \App\Models\Pembanding::class)
                            ->where('subject_id', $this->record->getKey())
                            ->latest('created_at')
                            ->limit(30)
                            ->get(),
                    ],
                )),

            Actions\EditAction::make()
                ->label('Edit Data')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(fn () => DataPembandingResource::getUrl('edit', ['record' => $this->record])),

            Actions\DeleteAction::make()
                ->label('Hapus Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('delete_pin')
                        ->label('PIN Penghapusan')
                        ->password()
                        ->revealable()
                        ->required()
                        ->inputMode('numeric')
                        ->rule('regex:/^[0-9]{4,12}$/')
                        ->helperText('Masukkan PIN yang diset di menu Security PIN.'),
                    Forms\Components\Textarea::make('deleted_reason')
                        ->label('Alasan Penghapusan')
                        ->rows(3)
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $storedPinHash = (string) SystemSetting::getValue('deletion_pin_hash', '');
                    $inputPin = trim((string) ($data['delete_pin'] ?? ''));

                    if ($storedPinHash === '') {
                        Notification::make()
                            ->title('PIN penghapusan belum diset')
                            ->body('Silakan set PIN terlebih dahulu di menu Security PIN.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $isValidPin = Hash::check($inputPin, $storedPinHash)
                        || hash_equals($storedPinHash, $inputPin); // fallback legacy jika pernah tersimpan plaintext

                    if (! $isValidPin) {
                        Notification::make()
                            ->title('PIN salah')
                            ->body('PIN penghapusan tidak valid.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (hash_equals($storedPinHash, $inputPin)) {
                        // Upgrade legacy plaintext pin ke hash.
                        SystemSetting::setValue('deletion_pin_hash', Hash::make($inputPin));
                    }

                    $this->record->forceFill([
                        'deleted_by_id' => auth()->id(),
                        'deleted_reason' => trim((string) ($data['deleted_reason'] ?? '')),
                    ])->save();

                    $this->record->delete();

                    Notification::make()
                        ->title('Data berhasil dihapus')
                        ->success()
                        ->send();

                    $this->redirect(DataPembandingResource::getUrl('index'));
                }),
        ];
    }
}
