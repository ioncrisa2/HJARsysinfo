<?php
namespace App\Services\Peruntukan;

class PeruntukanGroupService
{
    protected const GROUPS = [
        'perumahan' => [
            'rumah_tinggal',
            'villa',
            'townhouse',
            'unit_apartemen',
        ],
        'komersial' => [
            'ruko',
            'rukan',
            'mall',
            'perkantoran',
            'kios',
        ],
        'industri' => [
            'pabrik',
            'gudang',
        ],
        'campuran' => [
            'tanah_kosong',
            'campuran',
            'lainnya',
        ],
    ];

    protected array $peruntukanToGroup = [];

    public function getAllowedPeruntukan(?string $peruntukanSlug): array
    {
        if (! $peruntukanSlug) {
            return [];
        }

        $peruntukanSlug = strtolower($peruntukanSlug);

        return match ($peruntukanSlug) {
            // Rumah tinggal dan tanah kosong saling dipakai sebagai pembanding.
            'rumah_tinggal', 'tanah_kosong' => [
                'rumah_tinggal',
                'tanah_kosong',
            ],
            'ruko' => ['ruko'],
            'campuran' => [
                'tanah_kosong',
                'campuran',
            ],
            'gudang' => ['gudang'],
            default => $this->getAllowedByGroup($peruntukanSlug),
        };
    }

    public function getZoningScore(?string $a, ?string $b): int
    {
        if (! $a || ! $b) {
            return 0;
        }

        $this->buildGroupMapping();

        $a = strtolower($a);
        $b = strtolower($b);

        $groupA = $this->peruntukanToGroup[$a] ?? null;
        $groupB = $this->peruntukanToGroup[$b] ?? null;

        return ($groupA && $groupA === $groupB) ? 3 : 1;
    }

    protected function getAllowedByGroup(string $peruntukanSlug): array
    {
        $this->buildGroupMapping();

        $group = $this->peruntukanToGroup[$peruntukanSlug] ?? null;

        if (! $group) {
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
                $this->peruntukanToGroup[$type] = $groupName;
            }
        }
    }
}
