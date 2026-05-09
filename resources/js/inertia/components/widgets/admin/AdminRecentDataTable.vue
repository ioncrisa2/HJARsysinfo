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

</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-800">Data Terbaru Masuk</h3>
                <p class="text-xs text-slate-500">5 properti terakhir yang ditambahkan</p>
            </div>
            <Link 
                href="/admin/pembanding" 
                class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors"
            >
                Lihat Semua
            </Link>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Alamat</th>
                        <th class="px-6 py-3">Harga</th>
                        <th class="px-6 py-3">Listing</th>
                        <th class="px-6 py-3">Waktu</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="item in data" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-800 truncate max-w-[200px]" :title="item.alamat_data">
                                {{ item.alamat_data }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-900">{{ formatCurrency(item.harga) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span 
                                class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-tight text-white shadow-sm"
                                :style="{ backgroundColor: item.jenis_listing?.badge_color || '#64748b' }"
                            >
                                {{ item.jenis_listing?.name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            {{ item.created_at }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Link 
                                    :href="`/admin/pembanding/${item.id}`"
                                    class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                                    title="Lihat Detail"
                                >
                                    <i class="pi pi-eye" />
                                </Link>
                                <Link 
                                    :href="`/admin/pembanding/${item.id}/edit`"
                                    class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Edit Data"
                                >
                                    <i class="pi pi-pencil" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="data.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center">
                            <i class="pi pi-inbox text-3xl text-slate-200 block mb-3" />
                            <p class="text-slate-400">Belum ada data tersedia.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
