<script setup>
defineProps({
    price: { type: String, default: "n/a" },
    landArea: { type: String, default: "n/a" },
    buildingArea: { type: String, default: "n/a" },
    dataDate: { type: String, default: "n/a" },
});
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

        <!-- Harga — highlighted -->
        <div class="relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-500 to-amber-600 px-4 py-4 shadow-sm col-span-2 sm:col-span-1">
            <div class="absolute right-3 top-3 opacity-20">
                <i class="pi pi-tag text-white" style="font-size: 28px" />
            </div>
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-amber-100">
                Harga
            </p>
            <p class="break-all text-base font-black leading-tight text-white sm:text-lg">
                {{ price }}
            </p>
        </div>

        <!-- Luas Tanah -->
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="absolute right-3 top-3 opacity-[0.06]">
                <i class="pi pi-expand" style="font-size: 28px" />
            </div>
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                Luas Tanah
            </p>
            <p class="text-lg font-black text-slate-800">{{ landArea }}</p>
        </div>

        <!-- Luas Bangunan -->
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="absolute right-3 top-3 opacity-[0.06]">
                <i class="pi pi-home" style="font-size: 28px" />
            </div>
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                Luas Bangunan
            </p>
            <p class="text-lg font-black text-slate-800">{{ buildingArea }}</p>
        </div>

        <!-- Tanggal Data -->
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="absolute right-3 top-3 opacity-[0.06]">
                <i class="pi pi-calendar" style="font-size: 28px" />
            </div>
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                Tanggal Data
            </p>
            <p class="text-sm font-black leading-tight text-slate-800">{{ dataDate }}</p>
        </div>

    </div>
</template>
