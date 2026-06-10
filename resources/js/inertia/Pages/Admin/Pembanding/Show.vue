<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
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

const isSewa = props.record.is_sewa || props.record.jenis_listing?.name?.toLowerCase() === 'sewa';
</script>

<template>
    <AdminLayout :title="`Detail Data #${record.id} — Admin`">
        <Head :title="`Detail #${record.id}`" />

        <!-- Breadcrumb -->
        <div class="mb-6 text-sm font-bold text-slate-500 flex items-center gap-2">
            <Link href="/admin/pembanding" class="hover:text-slate-900 transition">Bank Data</Link>
            <i class="pi pi-angle-right text-[10px]" />
            <span class="text-slate-900">Detail #{{ record.id }}</span>
        </div>

        <!-- Header Section (No Card) -->
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-sm">
                        <div class="size-2 rounded-full" :style="{ backgroundColor: record.jenis_listing?.badge_color || '#f59e0b' }"></div>
                        {{ record.jenis_listing?.name || 'Listing' }}
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-sm">
                        <div class="size-2 rounded-full bg-slate-400"></div>
                        {{ record.jenis_objek?.name || 'Objek' }}
                    </div>
                    <div v-if="record.latitude && record.longitude" class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-amber-200 text-xs font-bold text-amber-800 bg-amber-50 shadow-sm">
                        <i class="pi pi-map-marker text-amber-500" />
                        GPS tersedia
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="`/admin/pembanding/${record.id}/history`">
                        <Button label="Riwayat" icon="pi pi-history" severity="secondary" outlined size="small" class="rounded-xl px-4 text-slate-700 font-bold bg-white" />
                    </Link>
                    <Button label="Request Hapus" icon="pi pi-trash" severity="secondary" outlined size="small" class="rounded-xl px-4 text-slate-700 font-bold bg-white" />
                    <Link :href="`/admin/pembanding/${record.id}/edit`">
                        <Button label="Edit" icon="pi pi-pencil" size="small" class="rounded-xl px-6 bg-slate-900 border-slate-900 hover:bg-slate-800 text-white font-bold" />
                    </Link>
                </div>
            </div>

            <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-3">{{ record.alamat_data }}</h1>
            
            <div class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-600">
                <div class="flex items-center gap-1.5">
                    <i class="pi pi-map-marker text-slate-400" />
                    {{ [record.village?.name, record.district?.name, record.regency?.name, record.province?.name].filter(Boolean).join(", ") || "-" }}
                </div>
                <div class="w-px h-4 bg-slate-300 hidden sm:block"></div>
                <div class="flex items-center gap-1.5">
                    <i class="pi pi-user text-slate-400" />
                    <span>Dibuat oleh <span class="text-slate-900">{{ record.creator?.name || 'System' }}</span></span>
                </div>
                <span class="text-slate-400 hidden sm:block">&middot;</span>
                <div>
                    {{ formatDate(record.created_at) }}
                </div>
            </div>
        </div>

        <!-- Metrics Strip Card -->
        <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl mb-6 bg-white overflow-hidden">
            <div class="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Harga</p>
                    <p class="text-xl font-black text-amber-600 tracking-tight">{{ formatCurrency(record.harga) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Luas Tanah</p>
                    <p class="text-lg font-black text-slate-900">{{ record.luas_tanah ? record.luas_tanah + ' m²' : 'n/a' }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Luas Bangunan</p>
                    <p class="text-lg font-black text-slate-900">{{ record.luas_bangunan ? record.luas_bangunan + ' m²' : 'n/a' }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Tanggal Data</p>
                    <p class="text-lg font-black text-slate-900">{{ formatDate(record.tanggal_data) }}</p>
                </div>
            </div>
        </UiSurface>

        <!-- 3-Column Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
            
            <!-- Column 1 -->
            <div class="space-y-6">
                <!-- Foto -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-camera text-amber-500" />
                            Foto
                        </h2>
                    </div>
                    <div class="aspect-video bg-slate-100 flex justify-center items-center relative">
                        <img v-if="record.image_url" :src="record.image_url" class="w-full h-full object-cover" />
                        <i v-else class="pi pi-image text-4xl text-slate-300" />
                    </div>
                </UiSurface>

                <!-- Informasi Umum -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-info-circle text-amber-500" />
                            Informasi Umum
                        </h2>
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <div class="space-y-1">
                            <p class="font-bold text-slate-500 text-xs">Alamat</p>
                            <p class="font-bold text-slate-900 leading-snug">{{ record.alamat_data || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <p class="font-bold text-slate-500 text-xs">Jenis Listing</p>
                            <p class="font-bold text-slate-900">{{ record.jenis_listing?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">Jenis Objek</p>
                            <p class="font-bold text-slate-900">{{ record.jenis_objek?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">Harga</p>
                            <p class="font-bold text-amber-600">{{ formatCurrency(record.harga) }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">Tanggal Data</p>
                            <p class="font-bold text-slate-900">{{ formatDate(record.tanggal_data) }}</p>
                        </div>
                    </div>
                </UiSurface>

                <!-- Notes Card (If Any) -->
                <UiSurface v-if="record.catatan" class="p-0 border border-amber-200 shadow-sm rounded-2xl bg-amber-50 overflow-hidden">
                    <div class="px-5 py-4 border-b border-amber-100">
                        <h2 class="text-sm font-black text-amber-900 flex items-center gap-2">
                            <i class="pi pi-align-left text-amber-600" />
                            Catatan
                        </h2>
                    </div>
                    <div class="p-5">
                        <p class="text-slate-800 text-sm leading-relaxed whitespace-pre-wrap font-medium">{{ record.catatan }}</p>
                    </div>
                </UiSurface>
            </div>

            <!-- Column 2 -->
            <div class="space-y-6">
                <!-- Spesifikasi -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-box text-amber-500" />
                            Spesifikasi
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-3 gap-y-6 gap-x-4">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Luas Tanah</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.luas_tanah ? record.luas_tanah + ' m²' : 'n/a' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Luas Bangunan</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.luas_bangunan ? record.luas_bangunan + ' m²' : 'n/a' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Tahun Bangun</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.tahun_bangun || 'n/a' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Lebar Depan</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.lebar_depan ? record.lebar_depan + ' m' : 'n/a' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Lebar Jalan</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.lebar_jalan ? record.lebar_jalan + ' m' : 'n/a' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Rasio Tapak</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ record.rasio_tapak || 'n/a' }}</p>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <!-- Kondisi & Legalitas -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-verified text-amber-500" />
                            Kondisi & Legalitas
                        </h2>
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Status Pemberi Info</p>
                            <p class="font-bold text-slate-900">{{ record.status_pemberi_informasi?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Bentuk Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.bentuk_tanah?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Dokumen Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.dokumen_tanah?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Posisi Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.posisi_tanah?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kondisi Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.kondisi_tanah?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Topografi</p>
                            <p class="font-bold text-slate-900">{{ record.topografi_ref?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Peruntukan</p>
                            <p class="font-bold text-slate-900">{{ record.peruntukan_ref?.name || "-" }}</p>
                        </div>
                    </div>
                </UiSurface>
            </div>

            <!-- Column 3 -->
            <div class="space-y-6">
                <!-- Peta Lokasi -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-map-marker text-amber-500" />
                            Peta Lokasi
                        </h2>
                        <a 
                            v-if="record.latitude && record.longitude"
                            :href="`https://www.google.com/maps?q=${record.latitude},${record.longitude}`" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                        >
                            <i class="pi pi-external-link text-[10px]" /> Maps
                        </a>
                    </div>
                    <div class="aspect-[4/3] bg-slate-100 relative overflow-hidden flex flex-col items-center justify-center">
                        <div class="absolute inset-0 bg-[url('https://maps.wikimedia.org/osm-intl/12/3342/2165.png')] bg-cover bg-center opacity-40"></div>
                        
                        <div v-if="record.latitude && record.longitude" class="relative z-10 flex flex-col items-center">
                            <i class="pi pi-map-marker text-4xl text-blue-500 drop-shadow-md mb-2" />
                            <div class="bg-white px-3 py-1.5 rounded shadow-sm text-xs font-bold text-slate-800 border border-slate-200">
                                {{ record.latitude }}, {{ record.longitude }}
                            </div>
                        </div>
                        <div v-else class="relative z-10 text-slate-400 font-bold text-sm">
                            Tidak ada koordinat
                        </div>
                    </div>
                </UiSurface>

                <!-- Lokasi -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-map text-amber-500" />
                            Lokasi
                        </h2>
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Provinsi</p>
                            <p class="font-bold text-slate-900">{{ record.province?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kabupaten/Kota</p>
                            <p class="font-bold text-slate-900">{{ record.regency?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kecamatan</p>
                            <p class="font-bold text-slate-900">{{ record.district?.name || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Desa/Kelurahan</p>
                            <p class="font-bold text-slate-900">{{ record.village?.name || "-" }}</p>
                        </div>
                    </div>
                </UiSurface>

                <!-- Kontak & Metadata -->
                <UiSurface class="p-0 border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="pi pi-user text-amber-500" />
                            Kontak & Metadata
                        </h2>
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Nama</p>
                            <p class="font-bold text-slate-900">{{ record.nama_pemberi_informasi || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Telepon</p>
                            <p class="font-bold text-slate-900">{{ record.nomer_telepon_pemberi_informasi ? `(+62) ${record.nomer_telepon_pemberi_informasi}` : "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Dibuat</p>
                            <p class="font-bold text-slate-900">{{ formatDate(record.created_at) }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Diperbarui</p>
                            <p class="font-bold text-slate-900">{{ formatDate(record.updated_at) }}</p>
                        </div>
                    </div>
                </UiSurface>
            </div>
            
        </div>
    </AdminLayout>
</template>
