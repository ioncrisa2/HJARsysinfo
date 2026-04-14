<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

const parseTimestamp = (value) => {
    if (!value) return null;

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const formatRelativeTime = (value) => {
    const date = parseTimestamp(value);
    if (!date) return "-";

    const diffMs = Date.now() - date.getTime();
    const isPast = diffMs >= 0;
    const absMs = Math.abs(diffMs);

    if (absMs < 60 * 1000) {
        return isPast ? "beberapa detik lalu" : "beberapa detik lagi";
    }

    const minutes = Math.floor(absMs / (60 * 1000));
    if (minutes < 60) {
        return isPast ? `${minutes} menit lalu` : `dalam ${minutes} menit`;
    }

    const hours = Math.floor(absMs / (60 * 60 * 1000));
    if (hours < 24) {
        return isPast ? `${hours} jam lalu` : `dalam ${hours} jam`;
    }

    const days = Math.floor(absMs / (24 * 60 * 60 * 1000));
    if (days < 30) {
        return isPast ? `${days} hari lalu` : `dalam ${days} hari`;
    }

    const months = Math.floor(days / 30);
    if (months < 12) {
        return isPast ? `${months} bulan lalu` : `dalam ${months} bulan`;
    }

    const years = Math.floor(days / 365);
    return isPast ? `${years} tahun lalu` : `dalam ${years} tahun`;
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i class="pi pi-clock text-amber-500 text-xs" />
                Data Terbaru
            </div>
            <Link href="/home/pembanding" class="text-xs font-semibold text-amber-700 hover:text-amber-900 transition-colors">
                Lihat Semua ->
            </Link>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold text-slate-500">
                        <th class="px-3 py-2">Foto</th>
                        <th class="px-3 py-2">Alamat</th>
                        <th class="px-3 py-2">Jenis</th>
                        <th class="px-3 py-2">Harga</th>
                        <th class="px-3 py-2">Diinput</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr
                        v-for="item in data"
                        :key="item.id"
                        class="hover:bg-slate-50/60"
                    >
                        <!-- Foto -->
                        <td class="px-3 py-2.5">
                            <div class="h-12 w-16 overflow-hidden rounded-md border border-slate-200 bg-slate-100">
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    alt="Foto pembanding"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-slate-300">
                                    <i class="pi pi-image text-xs" />
                                </div>
                            </div>
                        </td>

                        <!-- Alamat -->
                        <td class="px-3 py-2.5">
                            <p class="max-w-[200px] truncate font-medium text-slate-800">
                                {{ item.alamat || "Tanpa alamat" }}
                            </p>
                        </td>

                        <!-- Jenis badges -->
                        <td class="px-3 py-2.5">
                            <div class="flex flex-wrap gap-1">
                                <span v-if="item.jenis_listing" class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                    {{ item.jenis_listing }}
                                </span>
                                <span v-if="item.jenis_objek" class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                    {{ item.jenis_objek }}
                                </span>
                            </div>
                        </td>

                        <!-- Harga -->
                        <td class="ui-tabular px-3 py-2.5 font-semibold text-slate-900">
                            {{ formatCurrency(item.harga) }}
                        </td>

                        <!-- Diinput -->
                        <td class="px-3 py-2.5 text-xs text-slate-500">
                            {{ formatRelativeTime(item.created_at) }}
                        </td>

                        <!-- Action -->
                        <td class="px-3 py-2.5">
                            <Link
                                :href="`/home/pembanding/${item.id}`"
                                class="text-xs font-semibold text-amber-700 hover:text-amber-900 transition-colors"
                            >
                                Detail
                            </Link>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                            <i class="pi pi-inbox text-2xl text-slate-300 block mb-2" />
                            Belum ada data tersedia.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

