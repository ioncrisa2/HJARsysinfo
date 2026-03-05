<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembandingDeleteRequestResource\Pages;
use App\Models\PembandingDeleteRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PembandingDeleteRequestResource extends Resource
{
    protected static ?string $model = PembandingDeleteRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Bank Data';
    protected static ?string $navigationLabel = 'Request Hapus Data';
    protected static ?string $pluralLabel = 'Request Hapus Data';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Request')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembanding.id')
                    ->label('ID Data')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembanding.alamat_data')
                    ->label('Alamat Data')
                    ->limit(45)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Diminta Oleh')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan Hapus')
                    ->limit(70)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PembandingDeleteRequest::STATUS_PENDING => 'Menunggu',
                        PembandingDeleteRequest::STATUS_APPROVED => 'Disetujui',
                        PembandingDeleteRequest::STATUS_REJECTED => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PembandingDeleteRequest::STATUS_PENDING => 'warning',
                        PembandingDeleteRequest::STATUS_APPROVED => 'success',
                        PembandingDeleteRequest::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewer')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Waktu Review')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Request')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        PembandingDeleteRequest::STATUS_PENDING => 'Menunggu',
                        PembandingDeleteRequest::STATUS_APPROVED => 'Disetujui',
                        PembandingDeleteRequest::STATUS_REJECTED => 'Ditolak',
                    ])
                    ->default(PembandingDeleteRequest::STATUS_PENDING),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Permintaan Hapus')
                    ->modalDescription('Data pembanding akan dihapus setelah request disetujui.')
                    ->visible(fn (PembandingDeleteRequest $record): bool => $record->status === PembandingDeleteRequest::STATUS_PENDING)
                    ->action(function (PembandingDeleteRequest $record): void {
                        $reviewer = auth()->user();

                        $isProcessed = DB::transaction(function () use ($record, $reviewer): bool {
                            $requestRecord = PembandingDeleteRequest::query()
                                ->lockForUpdate()
                                ->find($record->id);

                            if (! $requestRecord || $requestRecord->status !== PembandingDeleteRequest::STATUS_PENDING) {
                                return false;
                            }

                            $requestRecord->update([
                                'status' => PembandingDeleteRequest::STATUS_APPROVED,
                                'reviewed_by_id' => $reviewer?->id,
                                'reviewed_at' => now(),
                                'review_note' => null,
                            ]);

                            $pembanding = $requestRecord->pembanding()
                                ->withTrashed()
                                ->lockForUpdate()
                                ->first();

                            if ($pembanding && ! $pembanding->trashed()) {
                                $pembanding->forceFill([
                                    'deleted_by_id' => $reviewer?->id,
                                    'deleted_reason' => $requestRecord->reason,
                                ])->save();

                                $pembanding->delete();
                            }

                            return true;
                        });

                        if (! $isProcessed) {
                            Notification::make()
                                ->title('Request sudah diproses sebelumnya.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Permintaan disetujui dan data telah dihapus.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('review_note')
                            ->label('Catatan Penolakan')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->visible(fn (PembandingDeleteRequest $record): bool => $record->status === PembandingDeleteRequest::STATUS_PENDING)
                    ->action(function (PembandingDeleteRequest $record, array $data): void {
                        $reviewer = auth()->user();

                        $isProcessed = DB::transaction(function () use ($record, $reviewer, $data): bool {
                            $requestRecord = PembandingDeleteRequest::query()
                                ->lockForUpdate()
                                ->find($record->id);

                            if (! $requestRecord || $requestRecord->status !== PembandingDeleteRequest::STATUS_PENDING) {
                                return false;
                            }

                            $requestRecord->update([
                                'status' => PembandingDeleteRequest::STATUS_REJECTED,
                                'reviewed_by_id' => $reviewer?->id,
                                'reviewed_at' => now(),
                                'review_note' => trim((string) ($data['review_note'] ?? '')),
                            ]);

                            return true;
                        });

                        if (! $isProcessed) {
                            Notification::make()
                                ->title('Request sudah diproses sebelumnya.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Permintaan berhasil ditolak.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'pembanding',
            'requestedBy',
            'reviewedBy',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembandingDeleteRequests::route('/'),
        ];
    }
}
