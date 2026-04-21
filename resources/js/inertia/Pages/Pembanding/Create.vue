<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import PembandingFormHeader from "../../components/pembanding/form/PembandingFormHeader.vue";
import PembandingFormTabs from "../../components/pembanding/form/PembandingFormTabs.vue";
import { useCascadingLocation } from "../../composables/useCascadingLocation";
import { formatDateValue } from "../../composables/useDateBridge";
import { useImageUploadPreview } from "../../composables/useImageUploadPreview";
import { usePhoneNumberField } from "../../composables/usePhoneNumberId";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    options: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const optionLists = computed(() => props.options ?? {});

const form = useForm({
    jenis_listing_id: null,
    jenis_objek_id: null,
    nama_pemberi_informasi: "",
    nomer_telepon_pemberi_informasi: "",
    status_pemberi_informasi_id: null,
    tanggal_data: new Date(),
    alamat_data: "",
    province_id: null,
    regency_id: null,
    district_id: null,
    village_id: null,
    latitude: "",
    longitude: "",
    image: null,
    luas_tanah: null,
    luas_bangunan: null,
    lebar_depan: null,
    lebar_jalan: null,
    tahun_bangun: null,
    rasio_tapak: "",
    bentuk_tanah_id: null,
    posisi_tanah_id: null,
    kondisi_tanah_id: null,
    topografi_id: null,
    dokumen_tanah_id: null,
    peruntukan_id: null,
    harga: null,
    jangka_waktu_sewa: null,
    satuan_waktu_sewa: "Tahun",
    catatan: "",
});

const activeTab = ref("umum");

const isTanah = computed(() => Number(form.jenis_objek_id ?? 0) === Number(optionLists.value.tanahId ?? -1));
const bangunanRequired = computed(() => !isTanah.value);

const { regencyOptions, districtOptions, villageOptions } = useCascadingLocation(form);
const { imagePreview, handleImageUpload, clearImage } = useImageUploadPreview(form);
const { sanitizePhoneNumberId } = usePhoneNumberField(form);

const postForm = (createAnother = false) =>
    form
        .transform((data) => ({
            ...data,
            tanggal_data: formatDateValue(data.tanggal_data),
            nomer_telepon_pemberi_informasi: sanitizePhoneNumberId(data.nomer_telepon_pemberi_informasi),
            create_another: createAnother ? 1 : 0,
        }))
        .post("/home/pembanding", {
            forceFormData: true,
            onSuccess: () => {
                if (!createAnother) return;
                form.reset();
                form.clearErrors();
                form.tanggal_data = new Date();
                clearImage();
                activeTab.value = "umum";
            },
        });

const submit = () => postForm(false);
const submitAndCreateAnother = () => postForm(true);

const numConfig = { mode: "decimal", locale: "id-ID", useGrouping: false, minFractionDigits: 0 };
const currencyConfig = {
    mode: "decimal",
    locale: "id-ID",
    useGrouping: true,
    minFractionDigits: 0,
    maxFractionDigits: 0,
    prefix: "Rp ",
};
</script>

<template>
    <Head title="Tambah Data Pembanding" />

    <div class="space-y-4 py-4">
        <PembandingFormHeader
            mode="create"
            :processing="form.processing"
            :flash-success="page.props.flash?.success"
            :active-tab="activeTab"
            @submit="submit"
            @submit-and-create-another="submitAndCreateAnother"
        />

        <PembandingFormTabs
            v-model:active-tab="activeTab"
            mode="create"
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
            @submit-and-create-another="submitAndCreateAnother"
        />
    </div>
</template>
