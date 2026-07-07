<?php

namespace App\Services\Pembanding;

use App\Models\BentukTanah;
use App\Models\District;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use App\Models\Village;
use Illuminate\Support\Collection;

class PembandingFormOptionsService
{
    /** @param array<string, mixed> $values */
    public function for(array $values = []): array
    {
        return [
            'provinces' => $this->options(Province::query()->orderBy('name')->get()),
            'regencies' => $this->options(($values['province_id'] ?? null)
                ? Regency::query()->where('province_id', $values['province_id'])->orderBy('name')->get()
                : collect()),
            'districts' => $this->options(($values['regency_id'] ?? null)
                ? District::query()->where('regency_id', $values['regency_id'])->orderBy('name')->get()
                : collect()),
            'villages' => $this->options(($values['district_id'] ?? null)
                ? Village::query()->where('district_id', $values['district_id'])->orderBy('name')->get()
                : collect()),
            'jenisListings' => $this->options(JenisListing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()),
            'jenisObjeks' => $this->options(JenisObjek::query()
                ->where('is_active', true)
                ->whereNotIn('slug', ['non-properti', 'non_properti', 'nonproperti', 'non_property', 'non-properties', 'non_properties'])
                ->whereRaw('LOWER(name) NOT LIKE ?', ['%non properti%'])
                ->orderBy('sort_order')->orderBy('name')->get()),
            'statusPemberiInfos' => $this->options(StatusPemberiInformasi::query()->orderBy('name')->get()),
            'bentukTanahs' => $this->options(BentukTanah::query()->orderBy('name')->get()),
            'posisiTanahs' => $this->options(PosisiTanah::query()->orderBy('name')->get()),
            'kondisiTanahs' => $this->options(KondisiTanah::query()->orderBy('name')->get()),
            'topografis' => $this->options(Topografi::query()->orderBy('name')->get()),
            'dokumenTanahs' => $this->options(DokumenTanah::query()->orderBy('name')->get()),
            'peruntukans' => $this->options(Peruntukan::query()->orderBy('name')->get()),
            'tanahId' => JenisObjek::query()->where('slug', 'tanah')->value('id'),
            'sawahId' => JenisObjek::query()->where('slug', 'sawah')->value('id'),
            'tanahKebunId' => JenisObjek::query()->where('slug', 'tanah_kebun')->value('id'),
        ];
    }

    private function options(Collection $items): array
    {
        return $items->map(fn ($item): array => [
            'label' => (string) $item->name,
            'value' => $item->id,
        ])->values()->all();
    }
}
