<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::button wire:click="save" color="primary">
            Simpan PIN
        </x-filament::button>

        <div class="text-sm text-gray-500">
            Catatan: PIN disimpan dalam bentuk hash (tidak bisa dilihat kembali).
        </div>
    </div>
</x-filament-panels::page>
