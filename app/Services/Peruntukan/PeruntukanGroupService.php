<?php
namespace App\Services\Peruntukan;

use App\Enums\Peruntukan;

class PeruntukanGroupService
{
    protected const GROUPS = [
        'perumahan' => [
            Peruntukan::RumahTinggal,
            Peruntukan::Villa,
            Peruntukan::Townhouse,
            Peruntukan::UnitApartemen,
        ],
        'komersial' => [
            Peruntukan::Ruko,
            Peruntukan::Rukan,
            Peruntukan::Mall,
            Peruntukan::Perkantoran,
            Peruntukan::Kios,
        ],
        'industri' => [
            Peruntukan::Pabrik,
            Peruntukan::Gudang,
        ],
        'campuran' => [
            Peruntukan::TanahKosong,
            Peruntukan::Campuran,
            Peruntukan::Lainnya,
        ],
    ];

    protected array $peruntukanToGroup = [];

    public function getAllowedPeruntukan(?Peruntukan $peruntukan): array
    {
        if (!$peruntukan) {
            return [];
        }

        return match ($peruntukan) {
            Peruntukan::Ruko => [Peruntukan::Ruko->value],
            Peruntukan::TanahKosong, Peruntukan::Campuran => [
                Peruntukan::TanahKosong->value,
                Peruntukan::Campuran->value,
            ],
            Peruntukan::Gudang => [Peruntukan::Gudang->value],
            default => $this->getAllowedByGroup($peruntukan),
        };
    }

    public function getZoningScore(?Peruntukan $a, ?Peruntukan $b): int
    {
        if (!$a || !$b) {
            return 0;
        }

        $this->buildGroupMapping();

        $groupA = $this->peruntukanToGroup[$a->value] ?? null;
        $groupB = $this->peruntukanToGroup[$b->value] ?? null;

        return ($groupA && $groupA === $groupB) ? 3 : 1;
    }

    protected function getAllowedByGroup(Peruntukan $peruntukan): array
    {
        $this->buildGroupMapping();

        $group = $this->peruntukanToGroup[$peruntukan->value] ?? null;

        if (!$group) {
            return [];
        }

        return array_keys(array_filter(
            $this->peruntukanToGroup,
            fn (string $candidateGroup) => $candidateGroup === $group
        ));
    }

    protected function buildGroupMapping(): void
    {
        if (!empty($this->peruntukanToGroup)) {
            return;
        }

        foreach (self::GROUPS as $groupName => $types) {
            foreach ($types as $type) {
                $this->peruntukanToGroup[$type->value] = $groupName;
            }
        }
    }
}
