<script setup>
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import PembandingFormHeader from "../../../components/pembanding/form/PembandingFormHeader.vue";
import PembandingFormTabs from "../../../components/pembanding/form/PembandingFormTabs.vue";
import { useCascadingLocation } from "../../../composables/useCascadingLocation";
import { formatDateValue } from "../../../composables/useDateBridge";
import { useImageUploadPreview } from "../../../composables/useImageUploadPreview";
import { usePhoneNumberField } from "../../../composables/usePhoneNumberId";

const props = defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    options: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const optionLists = computed(() => props.options ?? {});
const sourceRecord = props.record ?? {};

const form = useForm({
    jenis_listing_id: sourceRecord.jenis_listing_id ?? null,
    jenis_objek_id: sourceRecord.jenis_objek_id ?? null,
    nama_pemberi_informasi: sourceRecord.nama_pemberi_informasi ?? "",
    nomer_telepon_pemberi_informasi: sourceRecord.nomer_telepon_pemberi_informasi ?? "",
    status_pemberi_informasi_id: sourceRecord.status_pemberi_informasi_id ?? null,
    tanggal_data: sourceRecord.tanggal_data ? new Date(sourceRecord.tanggal_data) : new Date(),
    alamat_data: sourceRecord.alamat_data ?? "",
    province_id: sourceRecord.province_id ?? null,
    regency_id: sourceRecord.regency_id ?? null,
    district_id: sourceRecord.district_id ?? null,
    village_id: sourceRecord.village_id ?? null,
    latitude: sourceRecord.latitude ?? "",
    longitude: sourceRecord.longitude ?? "",
    image: null,
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

const activeTab = ref("umum");

const isTanah = computed(() =>
    [optionLists.value.tanahId, optionLists.value.sawahId, optionLists.value.tanahKebunId].some(
        (id) => Number(form.jenis_objek_id ?? 0) === Number(id ?? -1),
    ),
);
const bangunanRequired = computed(() => !isTanah.value);

const { regencyOptions, districtOptions, villageOptions } = useCascadingLocation(form, {
    preloadOnMounted: true,
});

const { imagePreview, handleImageUpload, clearImage } = useImageUploadPreview(form, {
    initialPreview: sourceRecord.image_url ?? null,
});

const { sanitizePhoneNumberId } = usePhoneNumberField(form);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            tanggal_data: formatDateValue(data.tanggal_data),
            nomer_telepon_pemberi_informasi: sanitizePhoneNumberId(data.nomer_telepon_pemberi_informasi),
        }))
        .post(`/admin/pembanding/${sourceRecord.id}`, { forceFormData: true });
};

const numConfig = { mode: "decimal", locale: "id-ID", useGrouping: true, minFractionDigits: 0 };
const currencyConfig = { ...numConfig, prefix: "Rp " };
</script>

<template>
    <AdminLayout :title="`Edit Data #${sourceRecord.id} — Admin`">

        <div class="mb-2">
            <Link href="/admin/pembanding" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition">
                <i class="pi pi-arrow-left text-xs" />
                Kembali ke Data
            </Link>
        </div>

        <div class="space-y-4 py-4">
            <PembandingFormHeader
                mode="edit"
                :record-id="sourceRecord.id"
                :address="sourceRecord.alamat_data ?? ''"
                :processing="form.processing"
                :is-dirty="form.isDirty"
                :flash-success="page.props.flash?.success"
                :active-tab="activeTab"
                @submit="submit"
            />

            <PembandingFormTabs
                v-model:active-tab="activeTab"
                mode="edit"
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
                :handle-image-upload="handleImageUpload"
                :clear-image="clearImage"
                @submit="submit"
            />
        </div>
    </AdminLayout>
</template>
