<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import PembandingShowBreadcrumb from "../../components/pembanding/show/PembandingShowBreadcrumb.vue";
import PembandingShowHeaderCard from "../../components/pembanding/show/PembandingShowHeaderCard.vue";
import PembandingShowStatsGrid from "../../components/pembanding/show/PembandingShowStatsGrid.vue";
import PembandingShowMediaPanel from "../../components/pembanding/show/PembandingShowMediaPanel.vue";
import PembandingShowInfoSections from "../../components/pembanding/show/PembandingShowInfoSections.vue";
import PembandingShowNotesCard from "../../components/pembanding/show/PembandingShowNotesCard.vue";
import PembandingShowBackButton from "../../components/pembanding/show/PembandingShowBackButton.vue";
import PembandingDeleteRequestDialog from "../../components/pembanding/show/PembandingDeleteRequestDialog.vue";

defineOptions({ layout: TopNavLayout });

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

const formatText = (value) => (hasValue(value) ? value : "n/a");

const formatCurrency = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatDate = (value) => {
    if (!value) return "n/a";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const formatArea = (value) => (hasValue(value) ? `${value} m2` : "n/a");

const stats = computed(() => ({
    price: formatCurrency(record.value.harga),
    landArea: formatArea(record.value.luas_tanah),
    buildingArea: formatArea(record.value.luas_bangunan),
    dataDate: formatDate(record.value.tanggal),
}));

const hasPendingDeleteRequest = computed(() => Boolean(record.value.has_pending_delete_request));
const canRequestDelete = computed(() => Boolean(record.value.id) && !hasPendingDeleteRequest.value);

const alertClass = computed(() => {
    if (alertState.tone === "danger") {
        return "border-red-200 bg-red-50 text-red-700";
    }

    if (alertState.tone === "warning") {
        return "border-amber-200 bg-amber-50 text-amber-700";
    }

    return "border-emerald-200 bg-emerald-50 text-emerald-700";
});

let alertHideTimer = null;
let secondAlertTimer = null;

const showAutoAlert = (message, tone = "success", duration = 2000) => {
    alertState.message = message;
    alertState.tone = tone;
    alertState.visible = true;

    if (alertHideTimer) {
        window.clearTimeout(alertHideTimer);
    }

    alertHideTimer = window.setTimeout(() => {
        alertState.visible = false;
    }, duration);
};

const showDeleteRequestAlerts = () => {
    showAutoAlert("Request hapus data dari anda telah dikirim", "success", 2000);

    if (secondAlertTimer) {
        window.clearTimeout(secondAlertTimer);
    }

    secondAlertTimer = window.setTimeout(() => {
        showAutoAlert("Mohon tunggu approval dari admin", "warning", 2000);
    }, 2000);
};

watch(
    flashSuccess,
    (value, previousValue) => {
        if (!value || value === previousValue) return;
        showDeleteRequestAlerts();
    },
    { immediate: true },
);

watch(
    flashError,
    (value, previousValue) => {
        if (!value || value === previousValue) return;
        showAutoAlert(value, "danger", 2000);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (alertHideTimer) {
        window.clearTimeout(alertHideTimer);
    }

    if (secondAlertTimer) {
        window.clearTimeout(secondAlertTimer);
    }
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
        `/home/pembanding/${record.value.id}/delete-request`,
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
        },
    );
};

const infoSections = computed(() => [
    {
        title: "Informasi Umum",
        icon: "pi-info-circle",
        items: [
            { label: "Alamat", value: formatText(record.value.alamat), full: true },
            { label: "Jenis Listing", value: formatText(record.value.jenis_listing) },
            { label: "Jenis Objek", value: formatText(record.value.jenis_objek) },
            { label: "Harga", value: formatCurrency(record.value.harga), highlight: true },
            { label: "Tanggal Data", value: formatDate(record.value.tanggal) },
        ],
    },
    {
        title: "Lokasi",
        icon: "pi-map-marker",
        items: [
            { label: "Provinsi", value: formatText(record.value.province) },
            { label: "Kabupaten/Kota", value: formatText(record.value.regency) },
            { label: "Kecamatan", value: formatText(record.value.district) },
            { label: "Desa/Kelurahan", value: formatText(record.value.village) },
            {
                label: "Koordinat",
                value:
                    hasValue(record.value.latitude) && hasValue(record.value.longitude)
                        ? `${record.value.latitude}, ${record.value.longitude}`
                        : "n/a",
                full: true,
            },
        ],
    },
    {
        title: "Spesifikasi",
        icon: "pi-objects-column",
        grid: true,
        items: [
            { label: "Luas Tanah", value: formatArea(record.value.luas_tanah) },
            { label: "Luas Bangunan", value: formatArea(record.value.luas_bangunan) },
            { label: "Tahun Bangun", value: formatText(record.value.tahun_bangun) },
            { label: "Lebar Depan", value: formatText(record.value.lebar_depan) },
            { label: "Lebar Jalan", value: formatText(record.value.lebar_jalan) },
            { label: "Rasio Tapak", value: formatText(record.value.rasio_tapak) },
        ],
    },
    {
        title: "Kondisi & Legalitas",
        icon: "pi-file-check",
        items: [
            { label: "Status Pemberi Info", value: formatText(record.value.status_pemberi_informasi) },
            { label: "Bentuk Tanah", value: formatText(record.value.bentuk_tanah) },
            { label: "Dokumen Tanah", value: formatText(record.value.dokumen_tanah) },
            { label: "Posisi Tanah", value: formatText(record.value.posisi_tanah) },
            { label: "Kondisi Tanah", value: formatText(record.value.kondisi_tanah) },
            { label: "Topografi", value: formatText(record.value.topografi) },
            { label: "Peruntukan", value: formatText(record.value.peruntukan) },
        ],
    },
    {
        title: "Kontak & Metadata",
        icon: "pi-user",
        items: [
            { label: "Nama Pemberi Info", value: formatText(record.value.nama_pemberi_informasi) },
            { label: "Telepon", value: formatText(record.value.nomer_telepon_pemberi_informasi) },
            { label: "Dibuat", value: formatDate(record.value.created_at) },
            { label: "Diperbarui", value: formatDate(record.value.updated_at) },
        ],
    },
]);
</script>

<template>
    <Head :title="`Detail #${record.id}`" />

    <div class="space-y-4 py-3 sm:py-5">
        <div
            v-if="alertState.visible"
            class="rounded-xl border px-4 py-3 text-sm font-medium"
            :class="alertClass"
        >
            {{ alertState.message }}
        </div>

        <PembandingShowBreadcrumb :record="record" />

        <div class="space-y-4">
            <PembandingShowHeaderCard
                :record="record"
                :created-at-label="formatDate(record.created_at)"
                :can-request-delete="canRequestDelete"
                :has-pending-delete-request="hasPendingDeleteRequest"
                @request-delete="openDeleteRequestModal"
            />

            <PembandingShowStatsGrid
                :price="stats.price"
                :land-area="stats.landArea"
                :building-area="stats.buildingArea"
                :data-date="stats.dataDate"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-4">
                <PembandingShowMediaPanel :record="record" />
                <PembandingShowNotesCard :note="record.catatan" />
            </div>
            <div class="space-y-4">
                <PembandingShowInfoSections :sections="infoSections" />
            </div>
        </div>

        <PembandingShowBackButton />

        <PembandingDeleteRequestDialog
            v-model:visible="deleteRequestModalVisible"
            v-model:reason="deleteRequestForm.reason"
            :processing="deleteRequestProcessing"
            @submit="submitDeleteRequest"
        />
    </div>
</template>
