<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import PembandingFormTabs from "../../components/pembanding/form/PembandingFormTabs.vue";
import { useCascadingLocation } from "../../composables/useCascadingLocation";
import { useImageUploadPreview } from "../../composables/useImageUploadPreview";
import { usePhoneNumberField } from "../../composables/usePhoneNumberId";

defineOptions({ layout: AppLayout });

const props = defineProps({
    batch: { type: Object, required: true },
    row: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    navigation: { type: Object, default: () => ({}) },
});

const page = usePage();
const baseUrl = "/app/pembanding-imports";
const sourceRecord = props.row.data ?? props.row.payload ?? props.row;
const optionLists = computed(() => props.options ?? {});
const activeTab = ref("umum");
const fieldTabs = {
    jenis_listing_id: "umum",
    jenis_objek_id: "umum",
    nama_pemberi_informasi: "umum",
    nomer_telepon_pemberi_informasi: "umum",
    status_pemberi_informasi_id: "umum",
    alamat_data: "lokasi",
    province_id: "lokasi",
    regency_id: "lokasi",
    district_id: "lokasi",
    village_id: "lokasi",
    latitude: "lokasi",
    longitude: "lokasi",
    image: "properti",
    luas_tanah: "properti",
    luas_bangunan: "properti",
    lebar_depan: "properti",
    lebar_jalan: "properti",
    tahun_bangun: "properti",
    rasio_tapak: "properti",
    bentuk_tanah_id: "properti",
    posisi_tanah_id: "properti",
    kondisi_tanah_id: "properti",
    topografi_id: "properti",
    dokumen_tanah_id: "properti",
    peruntukan_id: "properti",
    harga: "properti",
    jangka_waktu_sewa: "properti",
    satuan_waktu_sewa: "properti",
    catatan: "catatan",
};
const nextRequiredTab = computed(() => {
    const missingTabs = new Set((props.row.missing_fields ?? [])
        .map((item) => fieldTabs[item.key ?? item.field])
        .filter(Boolean));

    return ["umum", "lokasi", "properti", "catatan"].find((tab) => missingTabs.has(tab)) ?? null;
});

const form = useForm({
    jenis_listing_id: sourceRecord.jenis_listing_id ?? null,
    jenis_objek_id: sourceRecord.jenis_objek_id ?? null,
    nama_pemberi_informasi: sourceRecord.nama_pemberi_informasi ?? "",
    nomer_telepon_pemberi_informasi: sourceRecord.nomer_telepon_pemberi_informasi ?? "",
    status_pemberi_informasi_id: sourceRecord.status_pemberi_informasi_id ?? null,
    alamat_data: sourceRecord.alamat_data ?? "",
    province_id: sourceRecord.province_id ?? null,
    regency_id: sourceRecord.regency_id ?? null,
    district_id: sourceRecord.district_id ?? null,
    village_id: sourceRecord.village_id ?? null,
    latitude: sourceRecord.latitude ?? "",
    longitude: sourceRecord.longitude ?? "",
    image: null,
    remove_image: false,
    luas_tanah: sourceRecord.luas_tanah ?? null,
    luas_bangunan: sourceRecord.luas_bangunan ?? null,
    lebar_depan: sourceRecord.lebar_depan ?? null,
    lebar_jalan: sourceRecord.lebar_jalan ?? null,
    tahun_bangun: sourceRecord.tahun_bangun ?? null,
    rasio_tapak: sourceRecord.rasio_tapak ?? "",
    bentuk_tanah_id: sourceRecord.bentuk_tanah_id ?? null,
    posisi_tanah_id: sourceRecord.posisi_tanah_id ?? null,
    kondisi_tanah_id: sourceRecord.kondisi_tanah_id ?? null,
    topografi_id: sourceRecord.topografi_id ?? null,
    dokumen_tanah_id: sourceRecord.dokumen_tanah_id ?? null,
    peruntukan_id: sourceRecord.peruntukan_id ?? null,
    harga: sourceRecord.harga ?? null,
    jangka_waktu_sewa: sourceRecord.jangka_waktu_sewa ?? null,
    satuan_waktu_sewa: sourceRecord.satuan_waktu_sewa ?? "Bulan",
    catatan: sourceRecord.catatan ?? "",
    _method: "PUT",
});

const isTanah = computed(() =>
    [optionLists.value.tanahId, optionLists.value.sawahId, optionLists.value.tanahKebunId].some(
        (id) => Number(form.jenis_objek_id ?? 0) === Number(id ?? -1),
    ),
);
const bangunanRequired = computed(() => !isTanah.value);

const { regencyOptions, districtOptions, villageOptions } = useCascadingLocation(form, {
    preloadOnMounted: true,
});
const { imagePreview, handleImageUpload: applyImage, clearImage: clearImagePreview } = useImageUploadPreview(form, {
    initialPreview: props.row.image_url ?? sourceRecord.image_url ?? null,
});
const { sanitizePhoneNumberId } = usePhoneNumberField(form);

const handleImageUpload = (event) => {
    form.remove_image = false;
    applyImage(event);
};

const clearImage = () => {
    form.remove_image = true;
    clearImagePreview();
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            nomer_telepon_pemberi_informasi: sanitizePhoneNumberId(data.nomer_telepon_pemberi_informasi),
        }))
        .post(`${baseUrl}/${props.batch.id}/rows/${props.row.id}`, {
            forceFormData: true,
            preserveScroll: true,
        });
};

const goToNextRequired = () => {
    if (nextRequiredTab.value) activeTab.value = nextRequiredTab.value;
};

const previousUrl = computed(() => props.navigation.previous_url ?? props.navigation.previous?.url ?? null);
const nextUrl = computed(() => props.navigation.next_url ?? props.navigation.next?.url ?? null);
const numConfig = { mode: "decimal", locale: "id-ID", useGrouping: true, minFractionDigits: 0 };
const currencyConfig = { ...numConfig, maxFractionDigits: 0, prefix: "Rp " };
</script>

<template>
    <Head :title="`Lengkapi data baris ${props.row.source_row_number}`" />

    <div class="space-y-4 py-4">
        <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Navigasi draf">
            <Link :href="`${baseUrl}/${props.batch.id}`" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">
                <i class="pi pi-arrow-left" aria-hidden="true" /> Kembali ke daftar data
            </Link>
            <div class="flex items-center gap-2">
                <Link v-if="previousUrl" :href="previousUrl" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"><i class="pi pi-chevron-left" aria-hidden="true" /> Sebelumnya</Link>
                <span v-else class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-bold text-slate-400" aria-disabled="true"><i class="pi pi-chevron-left" aria-hidden="true" /> Sebelumnya</span>
                <Link v-if="nextUrl" :href="nextUrl" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">Berikutnya <i class="pi pi-chevron-right" aria-hidden="true" /></Link>
                <span v-else class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-bold text-slate-400" aria-disabled="true">Berikutnya <i class="pi pi-chevron-right" aria-hidden="true" /></span>
            </div>
        </nav>

        <header class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Periksa data satu per satu</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Lengkapi baris {{ props.row.source_row_number }}</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-600">Isian dari Excel sudah dimasukkan. Periksa kembali dan lengkapi bagian yang masih kosong atau salah.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button v-if="nextRequiredTab" type="button" class="min-h-11 rounded-lg border border-amber-300 bg-amber-50 px-4 text-sm font-bold text-amber-900 hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600" @click="goToNextRequired">
                        Ke bagian wajib berikutnya <i class="pi pi-arrow-right ml-2" aria-hidden="true" />
                    </button>
                    <button type="button" class="min-h-11 rounded-lg bg-amber-500 px-5 text-sm font-bold text-slate-950 hover:bg-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 disabled:cursor-not-allowed disabled:opacity-50" :disabled="form.processing || !form.isDirty" @click="submit">
                        <i v-if="form.processing" class="pi pi-spin pi-spinner mr-2" aria-hidden="true" />
                        <i v-else class="pi pi-save mr-2" aria-hidden="true" />
                        {{ form.processing ? 'Sedang menyimpan…' : 'Simpan draf' }}
                    </button>
                </div>
            </div>
            <div class="h-1 bg-slate-100"><div class="h-full bg-amber-500 transition-all" :style="{ width: `${((['umum', 'lokasi', 'properti', 'catatan'].indexOf(activeTab) + 1) / 4) * 100}%` }" /></div>
        </header>

        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-950" role="note">
            <p class="font-bold">Menyimpan halaman ini belum memasukkan data ke Data Pembanding.</p>
            <p class="mt-1">Draf baru dapat dimasukkan setelah semua isian wajib lengkap dan Anda memilihnya dari daftar data.</p>
        </div>

        <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900" role="status">
            <i class="pi pi-check-circle mr-2" aria-hidden="true" />{{ page.props.flash.success }}
        </div>

        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
            <p class="font-bold">Draf belum dapat disimpan.</p>
            <p class="mt-1">Periksa isian yang ditandai pada setiap bagian, lalu simpan kembali.</p>
        </div>

        <PembandingFormTabs
            v-model:active-tab="activeTab"
            mode="draft"
            :form="form"
            :options="optionLists"
            :regency-options="regencyOptions"
            :district-options="districtOptions"
            :village-options="villageOptions"
            :image-preview="imagePreview"
            :is-tanah="isTanah"
            :bangunan-required="bangunanRequired"
            :num-config="numConfig"
            :currency-config="currencyConfig"
            @upload-image="handleImageUpload"
            @clear-image="clearImage"
            @submit="submit"
        />
    </div>
</template>
