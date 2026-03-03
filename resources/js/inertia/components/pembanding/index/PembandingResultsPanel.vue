<script setup>
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { parseDateValue } from "../../../composables/useDateBridge";

const props = defineProps({
    records: {
        type: Object,
        default: () => ({ data: [], links: [], total: 0, from: 0, to: 0 }),
    },
});

const viewMode = ref("card");

const rows = computed(() => props.records?.data ?? []);
const links = computed(() => props.records?.links ?? []);
const total = computed(() => props.records?.total ?? 0);
const from = computed(() => props.records?.from ?? 0);
const to = computed(() => props.records?.to ?? 0);

const formatCurrency = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatDateLong = (value) => {
    const date = parseDateValue(value);
    if (!date) return "-";

    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const displayLabel = (label) => {
    if (label === "pagination.previous") return "< Sebelumnya";
    if (label === "pagination.next") return "Berikutnya >";
    return label;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between text-xs text-slate-400">
            <span>
                Menampilkan
                <span class="font-semibold text-slate-600">{{ from }} - {{ to }}</span>
                dari
                <span class="font-semibold text-slate-600">{{ total }}</span>
                data
            </span>

            <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1">
                <button class="rounded-md px-2 py-1 transition"
                    :class="viewMode === 'card' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700'"
                    @click="viewMode = 'card'">
                    <i class="pi pi-th-large text-xs" />
                </button>
                <button class="rounded-md px-2 py-1 transition"
                    :class="viewMode === 'list' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700'"
                    @click="viewMode = 'list'">
                    <i class="pi pi-list text-xs" />
                </button>
            </div>
        </div>

        <div
            v-if="viewMode === 'card'"
            class="grid gap-4 min-[560px]:grid-cols-2 min-[720px]:grid-cols-3 min-[1280px]:grid-cols-4 min-[1440px]:grid-cols-5"
        >
            <div v-for="item in rows" :key="item.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md min-w-0">
                <div class="relative h-44 min-[1440px]:h-40 w-full overflow-hidden bg-slate-100">
                    <img :src="item.image_url || 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=60'"
                        alt="Foto properti" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        loading="lazy" />
                    <div class="absolute left-3 top-3 flex gap-1.5">
                        <span v-if="item.jenis_listing"
                            class="rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-bold uppercase text-white shadow">
                            {{ item.jenis_listing }}
                        </span>
                        <span v-if="item.jenis_objek"
                            class="rounded-full bg-amber-500 px-2.5 py-0.5 text-[10px] font-bold uppercase text-white shadow">
                            {{ item.jenis_objek }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-1 flex-col p-3.5 min-[1280px]:p-4">
                    <p class="text-base font-bold text-slate-900">{{ formatCurrency(item.harga) }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">
                        {{ item.alamat_data || "Tanpa alamat" }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400 line-clamp-1">
                        {{ item.location || "Lokasi tidak tersedia" }}
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <span v-if="item.luas_tanah" class="flex items-center gap-1">
                            <i class="pi pi-expand text-slate-400" style="font-size:10px" />
                            {{ item.luas_tanah }} m2
                        </span>
                        <span v-if="item.tanggal_data" class="flex items-center gap-1">
                            <i class="pi pi-calendar text-slate-400" style="font-size:10px" />
                            {{ formatDateLong(item.tanggal_data) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="pi pi-map-marker text-slate-400" style="font-size:10px" />
                            <span v-if="item.latitude && item.longitude">Koordinat tersedia</span>
                            <span v-else class="text-slate-300">Tanpa koordinat</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 px-3.5 py-2.5 min-[1280px]:px-4 min-[1280px]:py-3">
                    <Link :href="`/home/pembanding/${item.id}/edit`"
                        class="text-xs min-[1280px]:text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                        Edit
                    </Link>
                    <Link :href="`/home/pembanding/${item.id}`"
                        class="text-xs min-[1280px]:text-sm font-semibold text-amber-600 hover:text-amber-800 transition-colors">
                        Detail
                    </Link>
                    <a v-if="item.latitude && item.longitude" :href="`https://www.google.com/maps?q=${item.latitude},${item.longitude}`"
                        target="_blank" class="text-xs min-[1280px]:text-sm font-medium text-slate-400 hover:text-slate-700 transition-colors">
                        Lihat Maps
                    </a>
                </div>
            </div>

            <div v-if="rows.length === 0"
                class="col-span-full flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center">
                <i class="pi pi-inbox text-3xl text-slate-300" />
                <div>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada data</p>
                    <p class="text-xs text-slate-400">Coba ubah filter pencarian Anda</p>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div v-for="item in rows" :key="item.id" class="group flex items-stretch hover:bg-slate-50 transition overflow-hidden">
                <div class="relative w-36 shrink-0 overflow-hidden bg-slate-100">
                    <img :src="item.image_url || 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=400&q=60'"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                    <div class="absolute left-2 top-2 flex flex-col gap-1">
                        <span v-if="item.jenis_listing"
                            class="rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">
                            {{ item.jenis_listing }}
                        </span>
                        <span v-if="item.jenis_objek"
                            class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">
                            {{ item.jenis_objek }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-1 items-center gap-4 px-4 py-3 min-w-0">
                    <div class="flex flex-1 flex-col min-w-0 overflow-hidden">
                        <span class="text-base font-bold text-slate-900 truncate">{{ formatCurrency(item.harga) }}</span>
                        <p class="text-sm text-slate-700 truncate max-w-xl line-clamp-1">{{ item.alamat_data || "Tanpa alamat" }}</p>
                        <p class="text-xs text-slate-400 truncate max-w-xl line-clamp-1">{{ item.location || "Lokasi tidak tersedia" }}</p>
                    </div>

                    <div class="hidden md:flex items-center gap-4 text-xs text-slate-400 shrink-0">
                        <span v-if="item.luas_tanah"><i class="pi pi-expand mr-1" />{{ item.luas_tanah }} m2</span>
                        <span v-if="item.tanggal_data"><i class="pi pi-calendar mr-1" />{{ formatDateLong(item.tanggal_data) }}</span>
                        <span v-if="item.latitude && item.longitude" class="text-emerald-500"><i class="pi pi-map-marker mr-1" />Koordinat</span>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 text-sm">
                        <Link :href="`/home/pembanding/${item.id}/edit`" class="font-semibold text-blue-600 hover:text-blue-800">Edit</Link>
                        <Link :href="`/home/pembanding/${item.id}`" class="font-semibold text-amber-600 hover:text-amber-800">Detail</Link>
                        <a v-if="item.latitude && item.longitude" :href="`https://www.google.com/maps?q=${item.latitude},${item.longitude}`"
                            target="_blank" class="text-slate-400 hover:text-slate-700">Maps</a>
                    </div>
                </div>
            </div>

            <div v-if="rows.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <i class="pi pi-inbox text-3xl text-slate-300" />
                <p class="text-sm font-semibold text-slate-500">Tidak ada data</p>
                <p class="text-xs text-slate-400">Coba ubah filter pencarian Anda</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-2">
            <template v-for="(link, index) in links" :key="`${link.label}-${index}`">
                <Link v-if="link.url" :href="link.url" preserve-state preserve-scroll
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="link.active ? 'border-amber-300 bg-amber-50 font-semibold text-amber-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                    <span v-html="displayLabel(link.label)" />
                </Link>
                <span v-else class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-1.5 text-sm text-slate-300">
                    <span v-html="displayLabel(link.label)" />
                </span>
            </template>
        </div>
    </div>
</template>
