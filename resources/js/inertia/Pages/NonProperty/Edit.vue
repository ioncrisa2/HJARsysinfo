<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import VehicleComparableForm from "../../components/non-property/VehicleComparableForm.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    record: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    asset_category: props.record.asset_category ?? "vehicle",
    asset_subtype: props.record.asset_subtype ?? "",
    brand: props.record.brand ?? "",
    model: props.record.model ?? "",
    variant: props.record.variant ?? "",
    manufacture_year: props.record.manufacture_year ?? "",
    serial_number: props.record.serial_number ?? "",
    registration_number: props.record.registration_number ?? "",
    listing_type: props.record.listing_type ?? "penawaran",
    source_platform: props.record.source_platform ?? "",
    source_name: props.record.source_name ?? "",
    source_phone: props.record.source_phone ?? "",
    source_url: props.record.source_url ?? "",
    location_country: props.record.location_country ?? "Indonesia",
    location_city: props.record.location_city ?? "",
    location_address: props.record.location_address ?? "",
    latitude: props.record.latitude ?? "",
    longitude: props.record.longitude ?? "",
    currency: props.record.currency ?? "IDR",
    price: props.record.price ?? "",
    assumed_discount_percent: props.record.assumed_discount_percent ?? "",
    data_date: props.record.data_date ?? "",
    asset_condition: props.record.asset_condition ?? "bekas",
    operational_status: props.record.operational_status ?? "",
    legal_document_status: props.record.legal_document_status ?? "",
    verification_status: props.record.verification_status ?? "unverified",
    confidence_score: props.record.confidence_score ?? "",
    notes: props.record.notes ?? "",
    vehicle_type: props.record.vehicle_type ?? "",
    axle_configuration: props.record.axle_configuration ?? "",
    odometer_km: props.record.odometer_km ?? "",
    transmission: props.record.transmission ?? "",
    fuel_type: props.record.fuel_type ?? "",
    engine_cc: props.record.engine_cc ?? "",
    payload_kg: props.record.payload_kg ?? "",
    body_type: props.record.body_type ?? "",
    drive_type: props.record.drive_type ?? "",
    equipment_type: props.record.equipment_type ?? "",
    hour_meter: props.record.hour_meter ?? "",
    operating_weight_kg: props.record.operating_weight_kg ?? "",
    bucket_capacity_m3: props.record.bucket_capacity_m3 ?? "",
    engine_power_hp: props.record.engine_power_hp ?? "",
    undercarriage_type: props.record.undercarriage_type ?? "",
    undercarriage_condition: props.record.undercarriage_condition ?? "",
    attachment: props.record.attachment ?? "",
    service_history_note: props.record.service_history_note ?? "",
    barge_type: props.record.barge_type ?? "",
    capacity_dwt: props.record.capacity_dwt ?? "",
    loa_m: props.record.loa_m ?? "",
    beam_m: props.record.beam_m ?? "",
    draft_m: props.record.draft_m ?? "",
    gross_tonnage: props.record.gross_tonnage ?? "",
    built_year: props.record.built_year ?? "",
    shipyard: props.record.shipyard ?? "",
    hull_material: props.record.hull_material ?? "",
    class_status: props.record.class_status ?? "",
    certificate_valid_until: props.record.certificate_valid_until ?? "",
    last_docking_date: props.record.last_docking_date ?? "",
    media_files: [],
    media_links: [{ external_url: "", caption: "" }],
    removed_media_ids: [],
    _method: "PUT",
});

const submit = () => {
    form.post(`/home/non-properti/${props.record.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Data Non Properti" />

    <div class="space-y-4 py-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-400">Fase 4</p>
            <h1 class="text-xl font-bold text-slate-900">Edit Data Non Properti</h1>
        </div>

        <VehicleComparableForm
            :form="form"
            :options="props.options"
            :existing-media="props.record.media ?? []"
            submit-label="Simpan Perubahan"
            :cancel-href="`/home/non-properti/${props.record.id}`"
            @submit="submit"
        />
    </div>
</template>
