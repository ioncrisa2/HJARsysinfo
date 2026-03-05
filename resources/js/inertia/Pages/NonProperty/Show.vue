<script setup>
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    record: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({
            edit: false,
            delete: false,
            view_history: false,
        }),
    },
});

const unitName = computed(() =>
    [props.record.brand, props.record.model, props.record.variant].filter(Boolean).join(" ")
);

const isVehicle = computed(() => props.record.asset_category === "vehicle");
const isHeavyEquipment = computed(() => props.record.asset_category === "heavy_equipment");
const isBarge = computed(() => props.record.asset_category === "barge");

const categoryLabel = computed(() => {
    if (props.record.asset_category === "heavy_equipment") return "Alat Berat";
    if (props.record.asset_category === "barge") return "Tongkang";
    return "Kendaraan";
});

const mediaList = computed(() =>
    (props.record.media ?? [])
        .slice()
        .sort((a, b) => Number(a.sort_order ?? 0) - Number(b.sort_order ?? 0))
);

const deleteDialogVisible = ref(false);
const deleteReason = ref("");
const deleteProcessing = ref(false);

const formatCurrency = (value, currency = "IDR") => {
    if (value === null || value === undefined || value === "") return "-";

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency,
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const formatDateTime = (value) => {
    if (!value) return "-";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

const openDeleteDialog = () => {
    if (!props.can?.delete) return;

    deleteReason.value = "";
    deleteDialogVisible.value = true;
};

const closeDeleteDialog = () => {
    if (deleteProcessing.value) return;

    deleteDialogVisible.value = false;
};

const submitDelete = () => {
    const reason = deleteReason.value.trim();

    if (!reason || deleteProcessing.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(`/home/non-properti/${props.record.id}`, {
        data: { reason },
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Detail ${record.comparable_code}`" />

    <div class="space-y-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Detail Data</p>
                <h1 class="text-xl font-bold text-slate-900">{{ unitName }}</h1>
                <p class="text-sm font-mono text-slate-500">{{ record.comparable_code }} | {{ categoryLabel }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href="/home/non-properti"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                >
                    Kembali
                </Link>
                <Link
                    v-if="can.view_history"
                    :href="`/home/non-properti/${record.id}/history`"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                >
                    Riwayat
                </Link>
                <Link
                    v-if="can.edit"
                    :href="`/home/non-properti/${record.id}/edit`"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
                >
                    Edit
                </Link>
                <button
                    v-if="can.delete"
                    type="button"
                    class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                    @click="openDeleteDialog"
                >
                    Hapus
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Informasi Umum</h2>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Kategori</dt><dd class="font-semibold text-slate-900">{{ categoryLabel }}</dd>
                    <dt class="text-slate-500">Subjenis</dt><dd class="font-semibold text-slate-900">{{ record.asset_subtype ?? "-" }}</dd>
                    <dt class="text-slate-500">Tahun</dt><dd class="font-semibold text-slate-900">{{ record.manufacture_year ?? "-" }}</dd>
                    <dt class="text-slate-500">Listing</dt><dd class="font-semibold text-slate-900">{{ record.listing_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Kondisi</dt><dd class="font-semibold text-slate-900">{{ record.asset_condition ?? "-" }}</dd>
                    <dt class="text-slate-500">Kota</dt><dd class="font-semibold text-slate-900">{{ record.location_city ?? "-" }}</dd>
                    <dt class="text-slate-500">Tanggal Data</dt><dd class="font-semibold text-slate-900">{{ record.data_date ?? "-" }}</dd>
                    <dt class="text-slate-500">Harga</dt><dd class="font-semibold text-slate-900">{{ formatCurrency(record.price, record.currency) }}</dd>
                </dl>
            </div>

            <div v-if="isVehicle" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Kendaraan</h2>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Tipe</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.vehicle_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Konfigurasi Sumbu</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.axle_configuration ?? "-" }}</dd>
                    <dt class="text-slate-500">Odometer</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.odometer_km ?? "-" }} km</dd>
                    <dt class="text-slate-500">Transmisi</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.transmission ?? "-" }}</dd>
                    <dt class="text-slate-500">Bahan Bakar</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.fuel_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Isi Silinder</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.engine_cc ?? "-" }} cc</dd>
                    <dt class="text-slate-500">Payload</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.payload_kg ?? "-" }} kg</dd>
                    <dt class="text-slate-500">Body</dt><dd class="font-semibold text-slate-900">{{ record.vehicle.body_type ?? "-" }}</dd>
                </dl>
            </div>

            <div v-if="isHeavyEquipment" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Alat Berat</h2>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Tipe</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.equipment_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Hour Meter</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.hour_meter ?? "-" }} jam</dd>
                    <dt class="text-slate-500">Operating Weight</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.operating_weight_kg ?? "-" }} kg</dd>
                    <dt class="text-slate-500">Bucket Capacity</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.bucket_capacity_m3 ?? "-" }} m3</dd>
                    <dt class="text-slate-500">Engine Power</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.engine_power_hp ?? "-" }} HP</dd>
                    <dt class="text-slate-500">Undercarriage</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.undercarriage_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Condition</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.undercarriage_condition ?? "-" }}</dd>
                    <dt class="text-slate-500">Attachment</dt><dd class="font-semibold text-slate-900">{{ record.heavy_equipment.attachment ?? "-" }}</dd>
                </dl>
            </div>

            <div v-if="isBarge" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Tongkang</h2>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Tipe</dt><dd class="font-semibold text-slate-900">{{ record.barge.barge_type ?? "-" }}</dd>
                    <dt class="text-slate-500">Capacity</dt><dd class="font-semibold text-slate-900">{{ record.barge.capacity_dwt ?? "-" }} DWT</dd>
                    <dt class="text-slate-500">LOA</dt><dd class="font-semibold text-slate-900">{{ record.barge.loa_m ?? "-" }} m</dd>
                    <dt class="text-slate-500">Beam</dt><dd class="font-semibold text-slate-900">{{ record.barge.beam_m ?? "-" }} m</dd>
                    <dt class="text-slate-500">Draft</dt><dd class="font-semibold text-slate-900">{{ record.barge.draft_m ?? "-" }} m</dd>
                    <dt class="text-slate-500">GT</dt><dd class="font-semibold text-slate-900">{{ record.barge.gross_tonnage ?? "-" }}</dd>
                    <dt class="text-slate-500">Tahun Bangun</dt><dd class="font-semibold text-slate-900">{{ record.barge.built_year ?? "-" }}</dd>
                    <dt class="text-slate-500">Shipyard</dt><dd class="font-semibold text-slate-900">{{ record.barge.shipyard ?? "-" }}</dd>
                </dl>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Media dan Lampiran</h2>

            <div v-if="mediaList.length" class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="item in mediaList" :key="item.id" class="rounded-lg border border-slate-200 p-3">
                    <div class="mb-2 text-xs font-semibold uppercase text-slate-500">{{ item.media_type }}</div>

                    <img
                        v-if="item.media_type === 'image' && item.file_url"
                        :src="item.file_url"
                        alt="Media Comparable"
                        class="h-32 w-full rounded object-cover"
                    />

                    <a
                        v-else-if="item.media_type === 'document' && item.file_url"
                        :href="item.file_url"
                        target="_blank"
                        class="text-sm font-semibold text-amber-700 underline"
                    >
                        Buka Dokumen
                    </a>

                    <a
                        v-else-if="item.media_type === 'link' && item.external_url"
                        :href="item.external_url"
                        target="_blank"
                        class="text-sm font-semibold text-amber-700 underline"
                    >
                        Buka Link
                    </a>

                    <p v-else class="text-sm text-slate-500">Lampiran tidak tersedia.</p>

                    <p class="mt-2 text-xs text-slate-500">
                        {{ item.caption || item.file_name || '-' }}
                    </p>
                </div>
            </div>

            <p v-else class="mt-3 text-sm text-slate-500">Belum ada media/lampiran.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Sumber dan Verifikasi</h2>
            <div class="mt-3 grid gap-2 text-sm md:grid-cols-2">
                <p><span class="text-slate-500">Sumber:</span> <span class="font-semibold text-slate-900">{{ record.source_name ?? "-" }}</span></p>
                <p><span class="text-slate-500">Telepon:</span> <span class="font-semibold text-slate-900">{{ record.source_phone ?? "-" }}</span></p>
                <p><span class="text-slate-500">Platform:</span> <span class="font-semibold text-slate-900">{{ record.source_platform ?? "-" }}</span></p>
                <p><span class="text-slate-500">Status Verifikasi:</span> <span class="font-semibold text-slate-900">{{ record.verification_status }}</span></p>
                <p><span class="text-slate-500">Confidence:</span> <span class="font-semibold text-slate-900">{{ record.confidence_score ?? "-" }}</span></p>
            </div>
            <p class="mt-2 text-sm">
                <span class="text-slate-500">URL:</span>
                <a v-if="record.source_url" :href="record.source_url" target="_blank" class="font-semibold text-amber-700 underline">
                    Buka Sumber
                </a>
                <span v-else class="font-semibold text-slate-900">-</span>
            </p>
            <p class="mt-2 text-sm"><span class="text-slate-500">Catatan:</span> <span class="font-semibold text-slate-900">{{ record.notes ?? "-" }}</span></p>
            <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <p>Dibuat: {{ formatDateTime(record.created_at) }} oleh {{ record.created_by ?? "-" }}</p>
                <p>Diperbarui: {{ formatDateTime(record.updated_at) }} oleh {{ record.updated_by ?? "-" }}</p>
            </div>
        </div>

        <div v-if="deleteDialogVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeDeleteDialog" />
            <div class="relative w-full max-w-xl rounded-xl border border-slate-200 bg-white p-5 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Hapus Data Non Properti</h3>
                <p class="mt-1 text-sm text-slate-500">Data akan masuk soft delete. Isi alasan penghapusan.</p>

                <label class="mt-4 block text-sm font-semibold text-slate-700">Alasan Penghapusan</label>
                <textarea
                    v-model="deleteReason"
                    rows="5"
                    maxlength="1000"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Contoh: data duplikat / sumber tidak valid"
                />

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        :disabled="deleteProcessing"
                        @click="closeDeleteDialog"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:bg-slate-300"
                        :disabled="deleteProcessing || deleteReason.trim().length === 0"
                        @click="submitDelete"
                    >
                        {{ deleteProcessing ? "Memproses..." : "Hapus Data" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
