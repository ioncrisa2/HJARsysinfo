<script setup>
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
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
    <div class="space-y-5 p-4 sm:p-5">
        <UiSectionHeader
            title="Informasi Umum"
            subtitle="Tentukan klasifikasi data, identitas pemberi informasi, dan waktu pengambilan data."
            icon="pi pi-info-circle"
        />

        <div class="grid gap-4 md:grid-cols-2">
            <UiField id="jenis_listing_id" label="Jenis Listing" :required="true" :error="form.errors.jenis_listing_id">
                <Select
                    v-model="form.jenis_listing_id"
                    :options="options.jenisListings ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih jenis listing"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="jenis_listing_id"
                />
            </UiField>

            <UiField id="jenis_objek_id" label="Jenis Objek" :required="true" :error="form.errors.jenis_objek_id">
                <Select
                    v-model="form.jenis_objek_id"
                    :options="options.jenisObjeks ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih jenis objek"
                    class="w-full rounded-xl bg-slate-50/50"
                    inputId="jenis_objek_id"
                />
            </UiField>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Pemberi Informasi & Waktu</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <UiField
                    id="nama_pemberi_informasi"
                    label="Nama Pemberi Informasi"
                    :required="true"
                    :error="form.errors.nama_pemberi_informasi"
                >
                    <InputText
                        v-model="form.nama_pemberi_informasi"
                        id="nama_pemberi_informasi"
                        placeholder="Nama lengkap pemberi informasi"
                        class="w-full rounded-xl bg-slate-50/50 border-slate-200"
                    />
                </UiField>

                <UiField
                    id="nomer_telepon_pemberi_informasi"
                    label="Nomor Telepon"
                    :error="form.errors.nomer_telepon_pemberi_informasi"
                    help="Opsional. Gunakan format Indonesia (mis. 0812...)."
                >
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none border-r border-slate-200 pr-3 mr-3">
                            <span class="text-xs font-bold text-slate-400">+62</span>
                        </div>
                        <InputText
                            v-model="form.nomer_telepon_pemberi_informasi"
                            id="nomer_telepon_pemberi_informasi"
                            placeholder="812xxxxxxxx"
                            class="w-full pl-16 rounded-xl bg-slate-50/50 border-slate-200 font-mono text-sm"
                        />
                    </div>
                </UiField>

                <UiField
                    id="status_pemberi_informasi_id"
                    label="Status Pemberi Informasi"
                    :error="form.errors.status_pemberi_informasi_id"
                >
                    <Select
                        v-model="form.status_pemberi_informasi_id"
                        :options="options.statusPemberiInfos ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih status"
                        show-clear
                        class="w-full rounded-xl bg-slate-50/50"
                        inputId="status_pemberi_informasi_id"
                    />
                </UiField>

                <UiField
                    id="tanggal_data"
                    label="Tanggal Data"
                    :required="true"
                    :error="form.errors.tanggal_data"
                    help="Tanggal survei atau saat data ditemukan."
                >
                    <DatePicker
                        v-model="form.tanggal_data"
                        inputId="tanggal_data"
                        show-icon
                        iconDisplay="input"
                        date-format="yy-mm-dd"
                        class="w-full rounded-xl bg-slate-50/50"
                    />
                </UiField>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-5">
            <Button
                label="Lanjut ke Lokasi"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="primary"
                class="rounded-xl px-6"
                @click="emit('next')"
            />
        </div>
    </div>
</template>


