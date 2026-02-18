<?php

namespace App\Filament\Resources\VillageResource\Pages;

use App\Filament\Resources\VillageResource;
use App\Services\Location\LocationIdGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use RuntimeException;

class CreateVillage extends CreateRecord
{
    protected static string $resource = VillageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $generator = app(LocationIdGenerator::class);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $data['id'] = $generator->nextVillageId((string) $data['district_id']);

            try {
                return static::getModel()::query()->create($data);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Gagal membuat data Desa / Kelurahan.');
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
