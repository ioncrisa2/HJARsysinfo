<script setup>
import { computed, watch } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        default: "Simpan",
    },
    cancelHref: {
        type: String,
        default: "/home/non-properti",
    },
    existingMedia: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["submit"]);

const subtypeOptions = computed(
    () => props.options.asset_subtypes_by_category?.[props.form.asset_category] ?? []
);

const isVehicle = computed(() => props.form.asset_category === "vehicle");
const isHeavyEquipment = computed(() => props.form.asset_category === "heavy_equipment");
const isBarge = computed(() => props.form.asset_category === "barge");
const existingMedia = computed(() => props.existingMedia ?? []);
const hasExistingMedia = computed(() => (props.existingMedia?.length ?? 0) > 0);

const removedMediaIds = computed(() => {
    if (!Array.isArray(props.form.removed_media_ids)) {
        props.form.removed_media_ids = [];
    }

    return props.form.removed_media_ids;
});

const selectedMediaFiles = computed(() => (Array.isArray(props.form.media_files) ? props.form.media_files : []));

watch(
    () => props.form.asset_category,
    () => {
        const subtypeValid = subtypeOptions.value.some((item) => item.value === props.form.asset_subtype);
        if (!subtypeValid) {
            props.form.asset_subtype = "";
        }
    }
);

if (!Array.isArray(props.form.media_links) || props.form.media_links.length === 0) {
    props.form.media_links = [{ external_url: "", caption: "" }];
}

if (!Array.isArray(props.form.removed_media_ids)) {
    props.form.removed_media_ids = [];
}

if (!Array.isArray(props.form.media_files)) {
    props.form.media_files = [];
}

const addMediaLink = () => {
    props.form.media_links.push({
        external_url: "",
        caption: "",
    });
};

const removeMediaLink = (index) => {
    if (props.form.media_links.length === 1) {
        props.form.media_links[0] = { external_url: "", caption: "" };
        return;
    }

    props.form.media_links.splice(index, 1);
};

const setMediaFiles = (event) => {
    props.form.media_files = Array.from(event.target.files ?? []);
};

const isMediaMarkedForRemoval = (mediaId) => removedMediaIds.value.includes(mediaId);

const markMediaForRemoval = (mediaId) => {
    if (isMediaMarkedForRemoval(mediaId)) {
        return;
    }

    removedMediaIds.value.push(mediaId);
};

const undoRemoveMedia = (mediaId) => {
    props.form.removed_media_ids = removedMediaIds.value.filter((id) => id !== mediaId);
};

const mediaLinkError = (index, field) => props.form.errors[`media_links.${index}.${field}`];
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Informasi Unit</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Kategori Aset *</label>
                    <select v-model="form.asset_category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.asset_categories" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.asset_category" class="mt-1 text-xs text-rose-600">{{ form.errors.asset_category }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Subjenis *</label>
                    <select v-model="form.asset_subtype" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in subtypeOptions" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.asset_subtype" class="mt-1 text-xs text-rose-600">{{ form.errors.asset_subtype }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Merek *</label>
                    <input v-model="form.brand" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.brand" class="mt-1 text-xs text-rose-600">{{ form.errors.brand }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Model *</label>
                    <input v-model="form.model" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.model" class="mt-1 text-xs text-rose-600">{{ form.errors.model }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Varian</label>
                    <input v-model="form.variant" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tahun</label>
                    <input
                        v-model="form.manufacture_year"
                        type="number"
                        min="1950"
                        :max="new Date().getFullYear() + 1"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nomor Seri/Hull</label>
                    <input v-model="form.serial_number" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nomor Registrasi</label>
                    <input
                        v-model="form.registration_number"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Sumber dan Lokasi</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Jenis Listing *</label>
                    <select v-model="form.listing_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.listing_types" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.listing_type" class="mt-1 text-xs text-rose-600">{{ form.errors.listing_type }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Platform Sumber</label>
                    <input
                        v-model="form.source_platform"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Nama Sumber</label>
                    <input v-model="form.source_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Telepon</label>
                    <input v-model="form.source_phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">URL Sumber</label>
                    <input v-model="form.source_url" type="url" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Negara</label>
                    <input
                        v-model="form.location_country"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Kota *</label>
                    <input v-model="form.location_city" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.location_city" class="mt-1 text-xs text-rose-600">{{ form.errors.location_city }}</p>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Alamat</label>
                    <textarea
                        v-model="form.location_address"
                        rows="2"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Harga dan Verifikasi</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Mata Uang *</label>
                    <input
                        v-model="form.currency"
                        type="text"
                        maxlength="3"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase"
                    />
                    <p v-if="form.errors.currency" class="mt-1 text-xs text-rose-600">{{ form.errors.currency }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Harga *</label>
                    <input
                        v-model="form.price"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                    <p v-if="form.errors.price" class="mt-1 text-xs text-rose-600">{{ form.errors.price }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Diskon Asumsi (%)</label>
                    <input
                        v-model="form.assumed_discount_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Data *</label>
                    <input v-model="form.data_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.data_date" class="mt-1 text-xs text-rose-600">{{ form.errors.data_date }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Kondisi *</label>
                    <select v-model="form.asset_condition" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.asset_conditions" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Status Operasional</label>
                    <select v-model="form.operational_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.operational_statuses" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Status Verifikasi *</label>
                    <select v-model="form.verification_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.verification_statuses" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Confidence Score (0-100)</label>
                    <input
                        v-model="form.confidence_score"
                        type="number"
                        min="0"
                        max="100"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Status Dokumen Legal</label>
                    <input
                        v-model="form.legal_document_status"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Catatan</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
            </div>
        </div>

        <div v-if="isVehicle" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Kendaraan</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tipe Kendaraan *</label>
                    <input v-model="form.vehicle_type" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.vehicle_type" class="mt-1 text-xs text-rose-600">{{ form.errors.vehicle_type }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Konfigurasi Sumbu</label>
                    <input
                        v-model="form.axle_configuration"
                        type="text"
                        placeholder="4x2 / 6x4 / 8x4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Odometer (km)</label>
                    <input v-model="form.odometer_km" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Transmisi</label>
                    <select v-model="form.transmission" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.transmission_types" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Bahan Bakar</label>
                    <select v-model="form.fuel_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.fuel_types" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Isi Silinder (cc)</label>
                    <input v-model="form.engine_cc" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Payload (kg)</label>
                    <input v-model="form.payload_kg" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Jenis Body</label>
                    <input v-model="form.body_type" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Drive Type</label>
                    <input v-model="form.drive_type" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
            </div>
        </div>

        <div v-if="isHeavyEquipment" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Alat Berat</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tipe Alat Berat *</label>
                    <input v-model="form.equipment_type" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.equipment_type" class="mt-1 text-xs text-rose-600">{{ form.errors.equipment_type }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Hour Meter</label>
                    <input v-model="form.hour_meter" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Operating Weight (kg)</label>
                    <input
                        v-model="form.operating_weight_kg"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Bucket Capacity (m3)</label>
                    <input
                        v-model="form.bucket_capacity_m3"
                        type="number"
                        min="0"
                        step="0.001"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Engine Power (HP)</label>
                    <input
                        v-model="form.engine_power_hp"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Undercarriage Type</label>
                    <select v-model="form.undercarriage_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.undercarriage_types" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Undercarriage Condition</label>
                    <input
                        v-model="form.undercarriage_condition"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Attachment</label>
                    <input v-model="form.attachment" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Riwayat Service/Overhaul</label>
                    <textarea
                        v-model="form.service_history_note"
                        rows="2"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>
        </div>

        <div v-if="isBarge" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Spesifikasi Tongkang</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tipe Tongkang *</label>
                    <input v-model="form.barge_type" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.barge_type" class="mt-1 text-xs text-rose-600">{{ form.errors.barge_type }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Capacity (DWT)</label>
                    <input v-model="form.capacity_dwt" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">LOA (m)</label>
                    <input v-model="form.loa_m" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Beam (m)</label>
                    <input v-model="form.beam_m" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Draft (m)</label>
                    <input v-model="form.draft_m" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Gross Tonnage</label>
                    <input
                        v-model="form.gross_tonnage"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tahun Bangun</label>
                    <input
                        v-model="form.built_year"
                        type="number"
                        min="1900"
                        :max="new Date().getFullYear() + 1"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Shipyard</label>
                    <input v-model="form.shipyard" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Hull Material</label>
                    <select v-model="form.hull_material" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option v-for="item in options.hull_material_options" :key="item.value" :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Class Status</label>
                    <input v-model="form.class_status" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Berlaku Sertifikat</label>
                    <input
                        v-model="form.certificate_valid_until"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Last Docking</label>
                    <input
                        v-model="form.last_docking_date"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Media dan Lampiran</h2>

            <div v-if="hasExistingMedia" class="mt-3 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Media Tersimpan</p>
                <div class="grid gap-2 md:grid-cols-2">
                    <div
                        v-for="item in existingMedia"
                        :key="item.id"
                        class="rounded-lg border px-3 py-2 text-sm"
                        :class="isMediaMarkedForRemoval(item.id) ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50'"
                    >
                        <p class="font-semibold text-slate-900">
                            {{ item.caption || item.file_name || `Media #${item.id}` }}
                        </p>

                        <template v-if="item.media_type === 'image' && item.file_url">
                            <a :href="item.file_url" target="_blank" class="text-xs text-amber-700 underline">Lihat Gambar</a>
                        </template>
                        <template v-else-if="item.media_type === 'document' && item.file_url">
                            <a :href="item.file_url" target="_blank" class="text-xs text-amber-700 underline">Lihat Dokumen</a>
                        </template>
                        <template v-else-if="item.media_type === 'link' && item.external_url">
                            <a :href="item.external_url" target="_blank" class="text-xs text-amber-700 underline">Buka Link</a>
                        </template>
                        <p v-else class="text-xs text-slate-500">-</p>

                        <div class="mt-2">
                            <button
                                v-if="!isMediaMarkedForRemoval(item.id)"
                                type="button"
                                class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700"
                                @click="markMediaForRemoval(item.id)"
                            >
                                Tandai Hapus
                            </button>
                            <button
                                v-else
                                type="button"
                                class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700"
                                @click="undoRemoveMedia(item.id)"
                            >
                                Batal Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Upload File (foto/dokumen)</label>
                    <input
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        @change="setMediaFiles"
                    />
                    <p class="mt-1 text-xs text-slate-500">Maks 20 file, masing-masing maksimal 10MB.</p>
                    <p v-if="form.errors.media_files" class="mt-1 text-xs text-rose-600">{{ form.errors.media_files }}</p>
                    <ul v-if="selectedMediaFiles.length" class="mt-2 list-disc space-y-1 pl-4 text-xs text-slate-600">
                        <li v-for="file in selectedMediaFiles" :key="`${file.name}-${file.size}`">
                            {{ file.name }}
                        </li>
                    </ul>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Link Media/Sumber Tambahan</label>
                    <div class="space-y-2">
                        <div
                            v-for="(link, index) in form.media_links"
                            :key="`media-link-${index}`"
                            class="rounded-lg border border-slate-200 p-2"
                        >
                            <input
                                v-model="link.external_url"
                                type="url"
                                placeholder="https://contoh.com/listing"
                                class="w-full rounded border border-slate-300 px-2 py-1 text-sm"
                            />
                            <p v-if="mediaLinkError(index, 'external_url')" class="mt-1 text-xs text-rose-600">
                                {{ mediaLinkError(index, "external_url") }}
                            </p>
                            <input
                                v-model="link.caption"
                                type="text"
                                placeholder="Keterangan link (opsional)"
                                class="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-sm"
                            />
                            <p v-if="mediaLinkError(index, 'caption')" class="mt-1 text-xs text-rose-600">
                                {{ mediaLinkError(index, "caption") }}
                            </p>
                            <button
                                type="button"
                                class="mt-2 rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700"
                                @click="removeMediaLink(index)"
                            >
                                Hapus Baris
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-2 rounded border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700"
                        @click="addMediaLink"
                    >
                        + Tambah Link
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="submit"
                class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900"
                :disabled="form.processing"
            >
                {{ submitLabel }}
            </button>
            <Link :href="cancelHref" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                Batal
            </Link>
        </div>
    </form>
</template>
