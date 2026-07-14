<?php

namespace App\Services\BulkExcelImport;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Database\Eloquent\Collection;

class BulkExcelImportLocationResolver
{
    public function __construct(private readonly BulkExcelImportValueNormalizer $normalizer) {}

    /** @return array{mapped: array<string, string|null>, warnings: array<int, array{field: string, message: string}>} */
    public function resolve(array $source): array
    {
        $warnings = [];
        $province = $this->match(Province::query()->get(['id', 'name']), $source['Propinsi'] ?? null);
        $regency = $province
            ? $this->match(Regency::query()->where('province_id', $province->id)->get(['id', 'name']), $source['Kota'] ?? null)
            : null;
        $district = $regency
            ? $this->match(District::query()->where('regency_id', $regency->id)->get(['id', 'name']), $source['Kecamatan'] ?? null)
            : null;
        $village = $district
            ? $this->match(Village::query()->where('district_id', $district->id)->get(['id', 'name']), $source['Desa'] ?? null)
            : null;

        foreach ([
            'province_id' => [$province, 'Provinsi', $source['Propinsi'] ?? null],
            'regency_id' => [$regency, 'Kabupaten/Kota', $source['Kota'] ?? null],
            'district_id' => [$district, 'Kecamatan', $source['Kecamatan'] ?? null],
            'village_id' => [$village, 'Desa/Kelurahan', $source['Desa'] ?? null],
        ] as $field => [$model, $label, $value]) {
            if (! $model) {
                $warnings[] = [
                    'field' => $field,
                    'message' => "{$label} \"{$value}\" belum dikenali. Pilih {$label} yang benar.",
                ];
            }
        }

        return [
            'mapped' => [
                'province_id' => $province?->getKey(),
                'regency_id' => $regency?->getKey(),
                'district_id' => $district?->getKey(),
                'village_id' => $village?->getKey(),
            ],
            'warnings' => $warnings,
        ];
    }

    private function match(Collection $options, mixed $source): ?object
    {
        $exactKey = $this->normalizer->exactLocationKey($source);
        $key = $this->normalizer->locationKey($source);
        if ($key === '') {
            return null;
        }

        $exactMatches = $options->filter(
            fn (object $option): bool => $this->normalizer->exactLocationKey($option->name) === $exactKey
        );
        if ($exactMatches->count() === 1) {
            return $exactMatches->first();
        }

        $matches = $options->filter(
            fn (object $option): bool => $this->normalizer->locationKey($option->name) === $key
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
