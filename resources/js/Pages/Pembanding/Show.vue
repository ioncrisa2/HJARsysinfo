<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import PembandingDeleteRequestDialog from "../../components/pembanding/show/PembandingDeleteRequestDialog.vue";
import PembandingLocationMap from "../../components/pembanding/show/PembandingLocationMap.vue";
import PembandingImage from "../../components/pembanding/PembandingImage.vue";
import { formatPhoneNumberId } from "../../composables/usePhoneNumberId";

defineOptions({ layout: AppLayout });

const page = usePage();

const props = defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
});

const record = computed(() => props.record ?? {});
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);
const deleteRequestSuccessMessage = "Permintaan hapus berhasil dikirim";

const deleteRequestModalVisible = ref(false);
const deleteRequestProcessing = ref(false);
const deleteRequestForm = reactive({
    reason: "",
});
const alertState = reactive({
    visible: false,
    message: "",
    tone: "success",
});

const hasValue = (value) => value !== null && value !== undefined && value !== "";
const formatPhone = (value) => formatPhoneNumberId(value) || "-";

const formatCurrency = (val) => {
    if (!val) return "-";
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const formatDate = (val) => {
    if (!val) return "-";
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatArea = (value) => (hasValue(value) ? `${value} m²` : "n/a");

const isSewa = computed(() => Boolean(record.value.is_sewa) || String(record.value.jenis_listing ?? "").toLowerCase() === "sewa");
const sewaPeriodeLabel = computed(() => {
    if (!isSewa.value) return "";
    if (record.value.sewa_periode_label) return record.value.sewa_periode_label;
    if (hasValue(record.value.jangka_waktu_sewa) && hasValue(record.value.satuan_waktu_sewa)) {
        return `per ${record.value.jangka_waktu_sewa} ${String(record.value.satuan_waktu_sewa).toLowerCase()}`;
    }
    return "Periode sewa belum diisi";
});

const hasPendingDeleteRequest = computed(() => Boolean(record.value.has_pending_delete_request));
const canRequestDelete = computed(() => Boolean(record.value.can_request_delete) && Boolean(record.value.id) && !hasPendingDeleteRequest.value);
const canUpdate = computed(() => Boolean(record.value.can_update));

const alertClass = computed(() => {
    if (alertState.tone === "danger") return "border-red-200 bg-red-50 text-red-700";
    if (alertState.tone === "warning") return "border-amber-200 bg-amber-50 text-amber-700";
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
});

let alertHideTimer = null;
let secondAlertTimer = null;

const showAutoAlert = (message, tone = "success", duration = 2000) => {
    alertState.message = message;
    alertState.tone = tone;
    alertState.visible = true;

    if (alertHideTimer) window.clearTimeout(alertHideTimer);
    alertHideTimer = window.setTimeout(() => {
        alertState.visible = false;
    }, duration);
};

const showDeleteRequestAlerts = () => {
    showAutoAlert("Request hapus data dari anda telah dikirim", "success", 2000);
    if (secondAlertTimer) window.clearTimeout(secondAlertTimer);
    secondAlertTimer = window.setTimeout(() => {
        showAutoAlert("Mohon tunggu persetujuan moderator", "warning", 2000);
    }, 2000);
};

watch(flashSuccess, (value, previousValue) => {
    if (!value || value === previousValue) return;
    if (String(value).startsWith(deleteRequestSuccessMessage)) {
        showDeleteRequestAlerts();
        return;
    }

    showAutoAlert(value, "success", 2000);
}, { immediate: true });

watch(flashError, (value, previousValue) => {
    if (!value || value === previousValue) return;
    showAutoAlert(value, "danger", 2000);
}, { immediate: true });

onBeforeUnmount(() => {
    if (alertHideTimer) window.clearTimeout(alertHideTimer);
    if (secondAlertTimer) window.clearTimeout(secondAlertTimer);
});

const openDeleteRequestModal = () => {
    if (!canRequestDelete.value) return;
    deleteRequestForm.reason = "";
    deleteRequestModalVisible.value = true;
};

const submitDeleteRequest = () => {
    const reason = deleteRequestForm.reason.trim();
    if (!reason || !canRequestDelete.value) return;

    deleteRequestProcessing.value = true;
    router.post(
        `/app/pembanding/${record.value.id}/delete-request`,
        { reason },
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteRequestModalVisible.value = false;
                deleteRequestForm.reason = "";
            },
            onFinish: () => {
                deleteRequestProcessing.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="`Detail #${record.id}`" />

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Alerts -->
        <div v-if="alertState.visible" class="rounded-xl border px-4 py-3 text-sm font-medium mb-4" :class="alertClass">
            {{ alertState.message }}
        </div>

        <!-- Breadcrumb -->
        <div class="mb-6 text-sm font-bold text-slate-500 flex items-center gap-2">
            <Link href="/app/pembanding" class="hover:text-slate-900 transition">Bank Data</Link>
            <i class="pi pi-angle-right text-[10px]" />
            <span class="text-slate-900">Detail #{{ record.id }}</span>
        </div>

        <!-- Header Section (No Card) -->
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <div v-if="record.jenis_listing" class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-sm">
                        <div class="size-2 rounded-full bg-amber-500"></div>
                        {{ record.jenis_listing }}
                    </div>
                    <div v-if="record.jenis_objek" class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-sm">
                        <div class="size-2 rounded-full bg-slate-400"></div>
                        {{ record.jenis_objek }}
                    </div>
                    <div v-if="record.latitude && record.longitude" class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-amber-200 text-xs font-bold text-amber-800 bg-amber-50 shadow-sm">
                        <i class="pi pi-map-marker text-amber-500" />
                        GPS tersedia
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="`/app/pembanding/${record.id}/history`">
                        <Button label="Riwayat" icon="pi pi-history" severity="secondary" outlined size="small" class="rounded-xl px-4 text-slate-700 font-bold bg-white" />
                    </Link>
                    <Button 
                        v-if="record.can_request_delete || hasPendingDeleteRequest"
                        :label="hasPendingDeleteRequest ? 'Menunggu Approval' : 'Request Hapus'" 
                        icon="pi pi-trash" 
                        :severity="canRequestDelete ? 'danger' : 'secondary'"
                        outlined 
                        size="small" 
                        class="rounded-xl px-4 font-bold bg-white"
                        :disabled="!canRequestDelete"
                        @click="openDeleteRequestModal"
                    />
                    <Link v-if="canUpdate" :href="`/app/pembanding/${record.id}/edit`">
                        <Button label="Edit" icon="pi pi-pencil" size="small" class="rounded-xl px-6 bg-slate-900 border-slate-900 hover:bg-slate-800 text-white font-bold" />
                    </Link>
                </div>
            </div>

            <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-3">{{ record.alamat || 'Tanpa Alamat' }}</h1>
            
            <div class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-600">
                <div v-if="record.location" class="flex items-center gap-1.5">
                    <i class="pi pi-map-marker text-slate-400" />
                    {{ record.location }}
                </div>
                <div v-if="record.location" class="w-px h-4 bg-slate-300 hidden sm:block"></div>
                <div class="flex items-center gap-1.5">
                    <i class="pi pi-user text-slate-400" />
                    <span>Dibuat oleh <span class="text-slate-900">{{ record.created_by || 'n/a' }}</span></span>
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
                    <p class="text-xs font-bold text-slate-500 mb-1.5">{{ isSewa ? 'Harga Sewa' : 'Harga' }}</p>
                    <p class="text-xl font-black text-amber-600 tracking-tight">{{ formatCurrency(record.harga) }}</p>
                    <p v-if="isSewa" class="text-[10px] font-bold text-amber-700 mt-0.5">{{ sewaPeriodeLabel }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Luas Tanah</p>
                    <p class="text-lg font-black text-slate-900">{{ formatArea(record.luas_tanah) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Luas Bangunan</p>
                    <p class="text-lg font-black text-slate-900">{{ formatArea(record.luas_bangunan) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold text-slate-500 mb-1.5">Tanggal Data</p>
                    <p class="text-lg font-black text-slate-900">{{ formatDate(record.tanggal) }}</p>
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
                    <div class="aspect-video bg-slate-100 relative overflow-hidden">
                        <PembandingImage :src="record.image_url" :alt="`Foto ${record.alamat || 'properti'}`" />
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
                            <p class="font-bold text-slate-900 leading-snug">{{ record.alamat || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <p class="font-bold text-slate-500 text-xs">Jenis Listing</p>
                            <p class="font-bold text-slate-900">{{ record.jenis_listing || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">Jenis Objek</p>
                            <p class="font-bold text-slate-900">{{ record.jenis_objek || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">{{ isSewa ? 'Harga Sewa' : 'Harga' }}</p>
                            <p class="font-bold text-amber-600">{{ formatCurrency(record.harga) }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="font-bold text-slate-500 text-xs">Tanggal Data</p>
                            <p class="font-bold text-slate-900">{{ formatDate(record.tanggal) }}</p>
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
                                <p class="text-sm font-black text-slate-900 mt-1">{{ formatArea(record.luas_tanah) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Luas Bangunan</p>
                                <p class="text-sm font-black text-slate-900 mt-1">{{ formatArea(record.luas_bangunan) }}</p>
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
                            <p class="font-bold text-slate-900">{{ record.status_pemberi_informasi || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Bentuk Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.bentuk_tanah || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Dokumen Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.dokumen_tanah || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Posisi Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.posisi_tanah || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kondisi Tanah</p>
                            <p class="font-bold text-slate-900">{{ record.kondisi_tanah || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Topografi</p>
                            <p class="font-bold text-slate-900">{{ record.topografi || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Peruntukan</p>
                            <p class="font-bold text-slate-900">{{ record.peruntukan || "-" }}</p>
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
                    <PembandingLocationMap
                        :latitude="record.latitude"
                        :longitude="record.longitude"
                        :popup-text="record.alamat || 'Lokasi properti'"
                    />
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
                            <p class="font-bold text-slate-900">{{ record.province || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kabupaten/Kota</p>
                            <p class="font-bold text-slate-900">{{ record.regency || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Kecamatan</p>
                            <p class="font-bold text-slate-900">{{ record.district || "-" }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-500 text-xs">Desa/Kelurahan</p>
                            <p class="font-bold text-slate-900">{{ record.village || "-" }}</p>
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
                            <p class="font-bold text-slate-900">{{ formatPhone(record.nomer_telepon_pemberi_informasi) }}</p>
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
        
        <PembandingDeleteRequestDialog
            v-model:visible="deleteRequestModalVisible"
            v-model:reason="deleteRequestForm.reason"
            :processing="deleteRequestProcessing"
            @submit="submitDeleteRequest"
        />
    </div>
</template>
