<script setup>
import Button from "primevue/button";
import Calendar from "primevue/calendar";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";
import UiField from "../../../ui/UiField.vue";

defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["next"]);
</script>

<template>
    <div class="space-y-6 p-4 sm:p-5">
        <UiSectionHeader
            title="Informasi Umum"
            subtitle="Jenis data, pemberi informasi, dan tanggal survei/listing."
            icon="pi pi-info-circle"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <UiField id="jenis_listing_id" label="Jenis listing" :required="true" :error="form.errors.jenis_listing_id">
                <Select
                    v-model="form.jenis_listing_id"
                    :options="options.jenisListings ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih jenis listing"
                    class="w-full filter-light"
                    inputId="jenis_listing_id"
                />
            </UiField>

            <UiField id="jenis_objek_id" label="Jenis objek" :required="true" :error="form.errors.jenis_objek_id">
                <Select
                    v-model="form.jenis_objek_id"
                    :options="options.jenisObjeks ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih jenis objek"
                    class="w-full filter-light"
                    inputId="jenis_objek_id"
                />
            </UiField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <UiField
                id="nama_pemberi_informasi"
                label="Nama pemberi informasi"
                :required="true"
                :error="form.errors.nama_pemberi_informasi"
            >
                <InputText
                    v-model="form.nama_pemberi_informasi"
                    id="nama_pemberi_informasi"
                    placeholder="Nama lengkap pemberi informasi"
                    class="w-full filter-light"
                />
            </UiField>

            <UiField
                id="nomer_telepon_pemberi_informasi"
                label="Nomor telepon"
                :error="form.errors.nomer_telepon_pemberi_informasi"
                help="Opsional. Gunakan format Indonesia bila memungkinkan."
            >
                <InputText
                    v-model="form.nomer_telepon_pemberi_informasi"
                    id="nomer_telepon_pemberi_informasi"
                    placeholder="mis. 081234567890"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>

            <UiField
                id="status_pemberi_informasi_id"
                label="Status pemberi info"
                :error="form.errors.status_pemberi_informasi_id"
            >
                <Select
                    v-model="form.status_pemberi_informasi_id"
                    :options="options.statusPemberiInfos ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih status"
                    show-clear
                    class="w-full filter-light"
                    inputId="status_pemberi_informasi_id"
                />
            </UiField>

            <UiField
                id="tanggal_data"
                label="Tanggal data"
                :required="true"
                :error="form.errors.tanggal_data"
                help="Tanggal survei atau tanggal listing ditemukan."
            >
                <Calendar
                    v-model="form.tanggal_data"
                    inputId="tanggal_data"
                    show-icon
                    date-format="yy-mm-dd"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <Button
                label="Lanjut ke Lokasi"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="secondary"
                outlined
                @click="emit('next')"
            />
        </div>
    </div>
</template>

