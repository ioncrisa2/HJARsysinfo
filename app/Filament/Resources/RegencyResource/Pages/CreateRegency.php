<?php

namespace App\Filament\Resources\RegencyResource\Pages;

use App\Filament\Resources\RegencyResource;
use App\Services\Location\LocationIdGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRegency extends CreateRecord
{
    protected static string $resource = RegencyResource::class;

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
            $data['id'] = $this->generator->nextRegencyId((string) $data['province_id']);

            return static::getModel()::query()->create($data);
        });
    }
}
