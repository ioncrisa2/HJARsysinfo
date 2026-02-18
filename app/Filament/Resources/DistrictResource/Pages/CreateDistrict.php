<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Services\Location\LocationIdGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use RuntimeException;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $generator = app(LocationIdGenerator::class);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $data['id'] = $generator->nextDistrictId((string) $data['regency_id']);

            try {
                return static::getModel()::query()->create($data);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Gagal membuat data Kecamatan.');
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
