<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import VehicleComparableForm from "../../components/non-property/VehicleComparableForm.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    options: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    asset_category: "vehicle",
    asset_subtype: "",
    brand: "",
    model: "",
    variant: "",
    manufacture_year: "",
    serial_number: "",
    registration_number: "",
    listing_type: "penawaran",
    source_platform: "",
    source_name: "",
    source_phone: "",
    source_url: "",
    location_country: "Indonesia",
    location_city: "",
    location_address: "",
    latitude: "",
    longitude: "",
    currency: "IDR",
    price: "",
    assumed_discount_percent: "",
    data_date: new Date().toISOString().slice(0, 10),
    asset_condition: "bekas",
    operational_status: "operasional",
    legal_document_status: "",
    verification_status: "unverified",
    confidence_score: "",
    notes: "",
    vehicle_type: "",
    axle_configuration: "",
    odometer_km: "",
    transmission: "",
    fuel_type: "",
    engine_cc: "",
    payload_kg: "",
    body_type: "",
    drive_type: "",
    equipment_type: "",
    hour_meter: "",
    operating_weight_kg: "",
    bucket_capacity_m3: "",
    engine_power_hp: "",
    undercarriage_type: "",
    undercarriage_condition: "",
    attachment: "",
    service_history_note: "",
    barge_type: "",
    capacity_dwt: "",
    loa_m: "",
    beam_m: "",
    draft_m: "",
    gross_tonnage: "",
    built_year: "",
    shipyard: "",
    hull_material: "",
    class_status: "",
    certificate_valid_until: "",
    last_docking_date: "",
    media_files: [],
    media_links: [{ external_url: "", caption: "" }],
    removed_media_ids: [],
});

const submit = () => {
    form.post("/home/non-properti", {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Data Non Properti" />

    <div class="space-y-4 py-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-400">Fase 4</p>
            <h1 class="text-xl font-bold text-slate-900">Tambah Data Non Properti</h1>
        </div>

        <VehicleComparableForm
            :form="form"
            :options="props.options"
            :existing-media="[]"
            submit-label="Simpan Data"
            cancel-href="/home/non-properti"
            @submit="submit"
        />
    </div>
</template>
