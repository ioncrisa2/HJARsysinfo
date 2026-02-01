<?php
namespace App\Services\Peruntukan;

use App\Enums\Peruntukan;

class PeruntukanGroupService
{
    protected const GROUPS = [
        'perumahan' => [
            'rumah-tinggal',
            'villa',
            'townhouse',
            'unit-apartemen',
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
            'tanah-kosong',
            'campuran',
            'lainnya',
        ],
    ];

    protected array $slugToGroup = [];

    /**
     * Get allowed peruntukan slugs for a given peruntukan slug
     *
     * @param string|null $peruntukanSlug
     * @return array Array of slug strings
     */
    public function getAllowedPeruntukan(?string $peruntukanSlug): array
    {
        if (!$peruntukanSlug) {
            return [];
        }

        $slug = strtolower($peruntukanSlug);

        return match ($slug) {
            'ruko' => ['ruko'],
            'tanah-kosong', 'campuran' => [
                'tanah-kosong',
                'campuran',
            ],
            'gudang' => ['gudang'],
            default => $this->getAllowedByGroup($slug),
        };
    }

    /**
     * Get zoning score for two peruntukan slugs
     *
     * @param string|null $slugA
     * @param string|null $slugB
     * @return int Score (3 = same group, 1 = different group, 0 = no match)
     */
    public function getZoningScoreBySlug(?string $slugA, ?string $slugB): int
    {
        if (!$slugA || !$slugB) {
            return 0;
        }

        $this->buildGroupMapping();

        $slugA = strtolower($slugA);
        $slugB = strtolower($slugB);

        $groupA = $this->slugToGroup[$slugA] ?? null;
        $groupB = $this->slugToGroup[$slugB] ?? null;

        return ($groupA && $groupA === $groupB) ? 3 : 1;
    }

    /**
     * DEPRECATED: For backwards compatibility with Enum-based code
     * Use getAllowedPeruntukan() with slug instead
     */
    public function getAllowedPeruntukanEnum(?Peruntukan $peruntukan): array
    {
        if (!$peruntukan) {
            return [];
        }

        return $this->getAllowedPeruntukan($peruntukan->value);
    }

    /**
     * DEPRECATED: For backwards compatibility with Enum-based code
     * Use getZoningScoreBySlug() instead
     */
    public function getZoningScore(?Peruntukan $a, ?Peruntukan $b): int
    {
        if (!$a || !$b) {
            return 0;
        }

        return $this->getZoningScoreBySlug($a->value, $b->value);
    }

    /**
     * Get all allowed slugs in the same group as the given slug
     */
    protected function getAllowedByGroup(string $slug): array
    {
        $this->buildGroupMapping();

        $group = $this->slugToGroup[$slug] ?? null;

        if (!$group) {
            return [];
        }

        return array_keys(array_filter(
            $this->slugToGroup,
            fn (string $candidateGroup) => $candidateGroup === $group
        ));
    }

    /**
     * Build mapping of slugs to group names
     */
    protected function buildGroupMapping(): void
    {
        if (!empty($this->slugToGroup)) {
            return;
        }

        foreach (self::GROUPS as $groupName => $slugs) {
            foreach ($slugs as $slug) {
                $this->slugToGroup[$slug] = $groupName;
            }
        }
    }
}
