<?php

namespace App\Services\Exports;

use App\Models\Pembanding;
use App\Models\User;
use App\Services\Pembanding\PembandingBrowseFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PembandingExportQueryService
{
    public function __construct(private readonly PembandingBrowseFilterService $filterService) {}

    /** @param array<string, mixed> $filters */
    public function query(User $user, array $filters, array $selectedIds = [], ?Carbon $snapshotAt = null): Builder
    {
        $query = $this->filterService->apply($this->baseQuery(), $this->normalizeFilters($filters));

        if (($filters['dataset'] ?? 'all') === 'complete') {
            $this->applyCompleteScope($query);
        } elseif (($filters['dataset'] ?? 'all') === 'issues') {
            $this->applyIssuesScope($query);
        }

        if (! $user->can('view_any_data::pembanding')) {
            $query->where('created_by', $user->id);
        }

        if ($selectedIds !== []) {
            $query->whereKey($selectedIds);
        }

        if ($snapshotAt) {
            $query->where('created_at', '<=', $snapshotAt);
        }

        return $query->orderByDesc('tanggal_data')->orderByDesc('id');
    }

    /** @param array<string, mixed> $filters */
    public function normalizeFilters(array $filters): array
    {
        return $this->filterService->normalize($filters);
    }

    /** @return array<int, int> */
    public function parseIds(string|array|null $ids): array
    {
        $values = is_array($ids) ? $ids : explode(',', (string) $ids);

        return collect($values)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(5000)
            ->values()
            ->all();
    }

    private function baseQuery(): Builder
    {
        return Pembanding::query()->with([
            'province', 'regency', 'district', 'village', 'jenisListing', 'jenisObjek',
            'statusPemberiInformasi', 'bentukTanah', 'dokumenTanah', 'posisiTanah',
            'kondisiTanah', 'topografiRef', 'peruntukanRef', 'creator:id,name', 'updater:id,name',
        ]);
    }

    private function applyCompleteScope(Builder $query): void
    {
        $query->whereNotNull('latitude')->whereNotNull('longitude')
            ->whereNotNull('image')->where('image', '!=', '')
            ->whereNotNull('harga')->where('harga', '>', 0)
            ->whereNotNull('luas_tanah')->where('luas_tanah', '>', 0)
            ->whereDate('tanggal_data', '>=', now()->subYears(2)->toDateString());

        foreach ($this->referenceRelations() as $relation) {
            $query->whereDoesntHave($relation, fn (Builder $reference) => $reference->where('is_active', false));
        }
    }

    private function applyIssuesScope(Builder $query): void
    {
        $query->where(function (Builder $issues): void {
            $issues->whereNull('latitude')->orWhereNull('longitude')
                ->orWhereNull('image')->orWhere('image', '')
                ->orWhereNull('harga')->orWhere('harga', '<=', 0)
                ->orWhereNull('luas_tanah')->orWhere('luas_tanah', '<=', 0)
                ->orWhereDate('tanggal_data', '<', now()->subYears(2)->toDateString());

            foreach ($this->referenceRelations() as $relation) {
                $issues->orWhereHas($relation, fn (Builder $reference) => $reference->where('is_active', false));
            }
        });
    }

    /** @return array<int, string> */
    private function referenceRelations(): array
    {
        return [
            'jenisListing', 'jenisObjek', 'statusPemberiInformasi', 'bentukTanah', 'dokumenTanah',
            'posisiTanah', 'kondisiTanah', 'topografiRef', 'peruntukanRef',
        ];
    }
}
