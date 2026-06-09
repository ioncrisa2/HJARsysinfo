<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Tag from "primevue/tag";
import Button from "primevue/button";

const props = defineProps({
    record: { type: Object, required: true },
});

const formatCurrency = (val) => {
    if (!val) return "-";
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const formatDate = (val) => {
    if (!val) return "-";
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const specs = [
    { label: "Luas Tanah", value: props.record.luas_tanah ? props.record.luas_tanah + " m²" : "-" },
    { label: "Luas Bangunan", value: props.record.luas_bangunan ? props.record.luas_bangunan + " m²" : "-" },
    { label: "Lebar Depan", value: props.record.lebar_depan ? props.record.lebar_depan + " m" : "-" },
    { label: "Lebar Jalan", value: props.record.lebar_jalan ? props.record.lebar_jalan + " m" : "-" },
    { label: "Tahun Bangun", value: props.record.tahun_bangun || "-" },
    { label: "Rasio Tapak", value: props.record.rasio_tapak || "-" },
];

const characteristics = [
    { label: "Bentuk Tanah", value: props.record.bentuk_tanah?.name || "-" },
    { label: "Posisi Tanah", value: props.record.posisi_tanah?.name || "-" },
    { label: "Kondisi Tanah", value: props.record.kondisi_tanah?.name || "-" },
    { label: "Topografi", value: props.record.topografi_ref?.name || "-" },
    { label: "Dokumen", value: props.record.dokumen_tanah?.name || "-" },
    { label: "Peruntukan", value: props.record.peruntukan_ref?.name || "-" },
];

const isSewa = props.record.is_sewa || props.record.jenis_listing?.name?.toLowerCase() === 'sewa';
const sewaPeriodeLabel = props.record.sewa_periode_label || (
    props.record.jangka_waktu_sewa && props.record.satuan_waktu_sewa
        ? `per ${props.record.jangka_waktu_sewa} ${String(props.record.satuan_waktu_sewa).toLowerCase()}`
        : null
);
</script>

<template>
    <AdminLayout :title="`Detail Data #${record.id} — Admin`">
        <Head :title="`Detail #${record.id}`" />

        <div class="mb-4">
            <Link href="/admin/pembanding" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-900 transition">
                <i class="pi pi-arrow-left text-[10px]" />
                Kembali ke Bank Data
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Primary Info & Specs -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Main Info Card -->
                <UiSurface class="overflow-hidden border-slate-200 shadow-xl shadow-slate-100 rounded-3xl">
                    <div class="relative h-96 w-full bg-slate-900 overflow-hidden group">
                        <img v-if="record.image_url" :src="record.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000" />
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-600 bg-slate-100">
                            <i class="pi pi-image text-6xl opacity-20" />
                        </div>
                        
                        <!-- Floating Badges -->
                        <div class="absolute top-6 left-6 flex flex-wrap gap-2">
                            <Tag 
                                :value="record.jenis_listing?.name" 
                                :style="{ backgroundColor: record.jenis_listing?.badge_color, color: 'white' }"
                                class="px-4 py-1.5 text-xs font-black uppercase tracking-widest rounded-full shadow-lg"
                            />
                            <Tag 
                                :value="record.jenis_objek?.name" 
                                class="bg-white text-slate-900 px-4 py-1.5 text-xs font-black uppercase tracking-widest rounded-full shadow-lg"
                            />
                        </div>

                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent pointer-events-none" />
                        
                        <div class="absolute bottom-8 left-8 right-8">
                            <h1 class="text-3xl font-black text-white tracking-tight mb-2">{{ record.alamat_data }}</h1>
                            <p class="text-slate-300 text-sm font-medium flex items-center gap-2">
                                <i class="pi pi-map-marker" />
                                {{ record.village?.name }}, {{ record.district?.name }}, {{ record.regency?.name }}
                            </p>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="flex flex-wrap items-center justify-between gap-6 mb-8 pb-8 border-b border-slate-100">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ isSewa ? 'Harga Sewa' : 'Nilai Properti' }}</p>
                                <p class="text-4xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(record.harga) }}</p>
                                <p v-if="isSewa" class="text-sm font-bold text-amber-600">{{ sewaPeriodeLabel || 'Periode sewa belum diisi' }}</p>
                                <p v-else-if="record.harga && record.luas_tanah" class="text-sm font-bold text-slate-500">
                                    {{ formatCurrency(record.harga / record.luas_tanah) }} / m²
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <Button label="Edit Data" icon="pi pi-pencil" severity="secondary" outlined class="rounded-xl px-6" as="Link" :href="`/admin/pembanding/${record.id}/edit`" />
                                <a 
                                    v-if="record.latitude && record.longitude"
                                    :href="`https://www.google.com/maps?q=${record.latitude},${record.longitude}`" 
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-2 bg-slate-900 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-200"
                                >
                                    <i class="pi pi-directions" />
                                    Petunjuk Arah
                                </a>
                            </div>
                        </div>

                        <!-- Technical Specs Grid -->
                        <div>
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-6">Spesifikasi Teknis</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-8">
                                <div v-for="spec in specs" :key="spec.label" class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ spec.label }}</p>
                                    <p class="text-base font-black text-slate-800 tracking-tight">{{ spec.value }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <!-- Characteristics Card -->
                <UiSurface class="p-8 border-slate-200 shadow-sm rounded-3xl">
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-6">Karakteristik & Legalitas</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-6">
                        <div v-for="char in characteristics" :key="char.label" class="space-y-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ char.label }}</p>
                            <p class="text-sm font-bold text-slate-700">{{ char.value }}</p>
                        </div>
                    </div>
                </UiSurface>

                <!-- Notes Card -->
                <UiSurface v-if="record.catatan" class="p-8 border-slate-200 shadow-sm rounded-3xl bg-slate-50/50">
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4">Catatan Tambahan</h2>
                    <p class="text-slate-600 leading-relaxed whitespace-pre-wrap">{{ record.catatan }}</p>
                </UiSurface>
            </div>

            <!-- Right Column: Sidebar Info -->
            <div class="lg:col-span-4 space-y-8">
                <!-- Location Info -->
                <UiSurface class="overflow-hidden border-slate-200 shadow-sm rounded-3xl">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <span class="text-xs font-black text-slate-900 uppercase tracking-widest">Informasi Lokasi</span>
                        <i class="pi pi-map text-slate-400" />
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <i class="pi pi-map-marker text-amber-500 mt-1" />
                                <div>
                                    <p class="text-xs font-bold text-slate-900">{{ record.alamat_data }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Alamat lengkap</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kecamatan</p>
                                    <p class="text-xs font-bold text-slate-700">{{ record.district?.name || "-" }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kelurahan</p>
                                    <p class="text-xs font-bold text-slate-700">{{ record.village?.name || "-" }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Map Container -->
                        <div class="h-48 rounded-2xl bg-slate-100 overflow-hidden relative border border-slate-200 group">
                            <div class="absolute inset-0 flex items-center justify-center bg-slate-900/5 transition-colors group-hover:bg-transparent">
                                <i class="pi pi-compass text-2xl text-slate-300 animate-spin-slow" />
                            </div>
                            <!-- Static/Interactive map would go here -->
                             <p class="absolute bottom-3 left-3 text-[9px] font-mono text-slate-500 bg-white/80 px-2 py-0.5 rounded-full border border-slate-200">
                                {{ record.latitude }}, {{ record.longitude }}
                             </p>
                        </div>
                    </div>
                </UiSurface>

                <!-- Information Source -->
                <UiSurface class="p-6 border-slate-200 shadow-sm rounded-3xl">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-6">Sumber Informasi</h2>
                    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
                        <div class="size-12 rounded-2xl bg-slate-900 flex items-center justify-center text-white font-black text-lg">
                            {{ record.nama_pemberi_informasi?.slice(0, 1).toUpperCase() || '?' }}
                        </div>
                        <div>
                            <p class="font-black text-slate-900 leading-tight">{{ record.nama_pemberi_informasi || "Tidak Diketahui" }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ record.status_pemberi_informasi?.name || "N/A" }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div v-if="record.nomer_telepon_pemberi_informasi" class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500">Telepon</span>
                            <span class="text-[11px] font-black text-slate-900 font-mono">+62 {{ record.nomer_telepon_pemberi_informasi }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500">Tanggal Data</span>
                            <span class="text-[11px] font-black text-slate-900">{{ formatDate(record.tanggal_data) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500">Penginput</span>
                            <span class="text-[11px] font-black text-slate-900">{{ record.creator?.name || "System" }}</span>
                        </div>
                    </div>
                </UiSurface>

                <!-- Quick Actions -->
                <div class="space-y-3">
                    <Button label="Duplikat Data" icon="pi pi-copy" severity="secondary" outlined class="w-full rounded-2xl py-3 font-bold text-sm" />
                    <Button label="Eksport PDF" icon="pi pi-file-pdf" severity="secondary" outlined class="w-full rounded-2xl py-3 font-bold text-sm" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-spin-slow {
    animation: spin 8s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
