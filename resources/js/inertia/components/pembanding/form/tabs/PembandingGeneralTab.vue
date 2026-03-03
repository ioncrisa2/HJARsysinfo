<script setup>
import Button from "primevue/button";
import Calendar from "primevue/calendar";
import InputText from "primevue/inputtext";
import Select from "primevue/select";

defineProps({
    form:    { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["next"]);
</script>

<template>
    <div class="space-y-6 p-5">

        <!-- ── Jenis Data ─────────────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-tag text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Jenis Data</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Jenis Listing <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.jenis_listing_id"
                        :options="options.jenisListings ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih jenis listing"
                        class="w-full"
                    />
                    <p v-if="form.errors.jenis_listing_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />
                        {{ form.errors.jenis_listing_id }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Jenis Objek <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form.jenis_objek_id"
                        :options="options.jenisObjeks ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih jenis objek"
                        class="w-full"
                    />
                    <p v-if="form.errors.jenis_objek_id" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />
                        {{ form.errors.jenis_objek_id }}
                    </p>
                </div>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Pemberi Informasi ───────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-user text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Pemberi Informasi</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Nama <span class="text-red-400">*</span>
                    </label>
                    <InputText
                        v-model="form.nama_pemberi_informasi"
                        placeholder="Nama lengkap pemberi informasi"
                        class="w-full"
                    />
                    <p v-if="form.errors.nama_pemberi_informasi" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />
                        {{ form.errors.nama_pemberi_informasi }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">Nomor Telepon</label>
                    <InputText
                        v-model="form.nomer_telepon_pemberi_informasi"
                        placeholder="mis. (+62) 812 3456 7890"
                        class="w-full"
                    />
                    <p v-if="form.errors.nomer_telepon_pemberi_informasi" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />
                        {{ form.errors.nomer_telepon_pemberi_informasi }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">Status Pemberi Info</label>
                    <Select
                        v-model="form.status_pemberi_informasi_id"
                        :options="options.statusPemberiInfos ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih status"
                        show-clear
                        class="w-full"
                    />
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Tanggal Data <span class="text-red-400">*</span>
                    </label>
                    <Calendar
                        v-model="form.tanggal_data"
                        show-icon
                        date-format="yy-mm-dd"
                        class="w-full"
                    />
                    <p class="text-[11px] text-slate-400">
                        Tanggal survei atau tanggal listing ditemukan.
                    </p>
                    <p v-if="form.errors.tanggal_data" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />
                        {{ form.errors.tanggal_data }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Nav -->
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
