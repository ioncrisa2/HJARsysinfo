<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import PembandingShowBreadcrumb from "../../components/pembanding/show/PembandingShowBreadcrumb.vue";
import PembandingShowHeaderCard from "../../components/pembanding/show/PembandingShowHeaderCard.vue";
import PembandingShowStatsGrid from "../../components/pembanding/show/PembandingShowStatsGrid.vue";
import PembandingShowMediaPanel from "../../components/pembanding/show/PembandingShowMediaPanel.vue";
import PembandingShowInfoSections from "../../components/pembanding/show/PembandingShowInfoSections.vue";
import PembandingShowNotesCard from "../../components/pembanding/show/PembandingShowNotesCard.vue";
import PembandingShowBackButton from "../../components/pembanding/show/PembandingShowBackButton.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
});

const record = computed(() => props.record ?? {});

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

const formatArea = (value) => (hasValue(value) ? `${value} m²` : "n/a");

const stats = computed(() => ({
    price: formatCurrency(record.value.harga),
    landArea: formatArea(record.value.luas_tanah),
    buildingArea: formatArea(record.value.luas_bangunan),
    dataDate: formatDate(record.value.tanggal),
}));

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

    <div class="space-y-5 py-3 sm:py-5">
        <PembandingShowBreadcrumb :record="record" />
        <PembandingShowHeaderCard :record="record" :created-at-label="formatDate(record.created_at)" />
        <PembandingShowStatsGrid
            :price="stats.price"
            :land-area="stats.landArea"
            :building-area="stats.buildingArea"
            :data-date="stats.dataDate"
        />
        <PembandingShowMediaPanel :record="record" />
        <PembandingShowInfoSections :sections="infoSections" />
        <PembandingShowNotesCard :note="record.catatan" />
        <PembandingShowBackButton />
    </div>
</template>
