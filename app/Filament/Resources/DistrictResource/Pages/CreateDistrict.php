<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Services\Location\LocationIdGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    public function __construct(private readonly LocationIdGenerator $generator)
    {
        parent::__construct();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $data['id'] = $this->generator->nextDistrictId((string) $data['regency_id']);

            return static::getModel()::query()->create($data);
        });
    }
}
