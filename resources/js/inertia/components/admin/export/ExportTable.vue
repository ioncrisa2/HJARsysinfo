<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import Tag from "primevue/tag";
import UiEmptyState from "../../ui/UiEmptyState.vue";

const props = defineProps({
    records: { type: Object, required: true },
    selectedIds: { type: Array, required: true },
    allVisibleSelected: { type: Boolean, default: false },
    summary: { type: Object, required: true },
});

const emit = defineEmits(["toggleAllVisible", "toggleSelected", "resetFilters"]);

const pricePeriodLabel = (record) => {
    if (!record?.is_sewa) return null;
    return record.sewa_periode_label || (record.jangka_waktu_sewa && record.satuan_waktu_sewa
        ? `per ${record.jangka_waktu_sewa} ${String(record.satuan_waktu_sewa).toLowerCase()}`
        : "periode sewa belum diisi");
};

const formatDate = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
};

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

const formatCurrency = (value) => {
    if (!value) return "-";
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                <tr>
                    <th class="w-12 px-5 py-4">
                        <button
                            type="button"
                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                            aria-label="Pilih semua data terlihat"
                            @click="emit('toggleAllVisible')"
                        >
                            <i v-if="allVisibleSelected" class="pi pi-check text-[10px] text-slate-700" />
                        </button>
                    </th>
                    <th class="px-5 py-4">Data</th>
                    <th class="px-5 py-4">Lokasi</th>
                    <th class="px-5 py-4">Tipe</th>
                    <th class="px-5 py-4 text-right">Harga</th>
                    <th class="px-5 py-4 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                <tr v-for="record in records.data" :key="record.id" class="hover:bg-slate-50">
                    <td class="px-5 py-4">
                        <button
                            type="button"
                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                            :aria-label="`Pilih data #${record.id}`"
                            @click="emit('toggleSelected', record.id)"
                        >
                            <i v-if="selectedIds.includes(record.id)" class="pi pi-check text-[10px] text-slate-700" />
                        </button>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                <img v-if="record.image_url" :src="record.image_url" alt="" class="size-full object-cover" />
                                <i v-else class="pi pi-image text-slate-300" />
                            </div>
                            <div class="min-w-0">
                                <p class="max-w-sm truncate font-bold text-slate-900" :title="record.alamat_data">
                                    {{ record.alamat_data || "Tanpa alamat" }}
                                </p>
                                <p class="ui-tabular mt-1 text-xs font-semibold text-slate-500">
                                    #{{ record.id }} · {{ formatDate(record.tanggal_data) }}
                                </p>
                                <p class="mt-1 max-w-sm truncate text-xs text-slate-500">
                                    {{ record.nama_pemberi_informasi || "Pemberi informasi belum diisi" }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <p class="max-w-xs truncate font-semibold text-slate-700">
                            {{ [record.village, record.district].filter(Boolean).join(", ") || "-" }}
                        </p>
                        <p class="mt-1 max-w-xs truncate text-xs text-slate-500">
                            {{ [record.regency, record.province].filter(Boolean).join(", ") || "-" }}
                        </p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col items-start gap-1.5">
                            <Tag v-if="record.jenis_listing" :value="record.jenis_listing" severity="info" />
                            <Tag v-if="record.jenis_objek" :value="record.jenis_objek" severity="secondary" />
                            <span class="text-xs font-medium text-slate-500">
                                LT {{ record.luas_tanah ?? "-" }} / LB {{ record.luas_bangunan ?? "-" }}
                            </span>
                        </div>
                    </td>
                    <td class="ui-tabular px-5 py-4 text-right font-bold text-slate-900">
                        {{ formatCurrency(record.harga) }}
                        <p v-if="record.is_sewa" class="mt-1 text-xs font-semibold text-amber-700">
                            {{ pricePeriodLabel(record) }}
                        </p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <Link
                            :href="`/admin/pembanding/${record.id}`"
                            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                            aria-label="Lihat detail"
                        >
                            <i class="pi pi-eye text-sm" />
                        </Link>
                    </td>
                </tr>

                <tr v-if="records.data.length === 0">
                    <td colspan="6" class="px-5 py-8">
                        <UiEmptyState
                            title="Tidak ada data"
                            description="Ubah filter untuk menemukan data yang bisa diexport."
                            icon="pi pi-file-export"
                        >
                            <template #actions>
                                <Button label="Reset Filter" icon="pi pi-filter-slash" size="small" @click="emit('resetFilters')" />
                            </template>
                        </UiEmptyState>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        v-if="records.links?.length > 3"
        class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="ui-tabular text-xs font-semibold text-slate-500">
            Menampilkan {{ records.from || 0 }}-{{ records.to || 0 }} dari {{ formatNumber(records.total) }} data
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in records.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                    :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-300"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
