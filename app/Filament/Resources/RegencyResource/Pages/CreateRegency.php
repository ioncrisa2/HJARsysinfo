<?php

namespace App\Filament\Resources\RegencyResource\Pages;

use App\Filament\Resources\RegencyResource;
use App\Services\Location\LocationIdGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use RuntimeException;

class CreateRegency extends CreateRecord
{
    protected static string $resource = RegencyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $generator = app(LocationIdGenerator::class);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $data['id'] = $generator->nextRegencyId((string) $data['province_id']);

            try {
                return static::getModel()::query()->create($data);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Gagal membuat data Kabupaten / Kota.');
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
