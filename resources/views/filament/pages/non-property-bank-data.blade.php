<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Bank Data Non Properti
            </x-slot>

            <x-slot name="description">
                Fase 5 aktif. Monitoring data aktif dan data terhapus sekarang tersedia di panel super_admin.
            </x-slot>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-500">Total Data</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $total }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-500">Data Terverifikasi</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-600">{{ $verified }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-500">Tanggal Data Terakhir</div>
                    <div class="mt-1 text-base font-semibold text-gray-900">{{ $lastDataDate ?? '-' }}</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-500">Data Aktif</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $activeTotal }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-500">Data Terhapus</div>
                    <div class="mt-1 text-2xl font-semibold text-rose-600">{{ $deletedTotal }}</div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\NonPropertyComparableResource::getUrl('index')"
                    color="warning"
                >
                    Kelola Data Aktif
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\TrashedNonPropertyComparableResource::getUrl('index')"
                    color="gray"
                >
                    Lihat Data Terhapus
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
