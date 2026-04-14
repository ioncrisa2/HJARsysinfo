<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";
import UiField from "../../../ui/UiField.vue";
import UiSurface from "../../../ui/UiSurface.vue";

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    mode: { type: String, default: "create" },
    imagePreview: { type: String, default: null },
    isTanah: { type: Boolean, default: false },
    bangunanRequired: { type: Boolean, default: true },
    numConfig: { type: Object, default: () => ({}) },
    currencyConfig: { type: Object, default: () => ({}) },
    handleImageUpload: { type: Function, default: null },
    clearImage: { type: Function, default: null },
});

const emit = defineEmits(["prev", "next"]);

const isCreate = props.mode === "create";
const isDragging = ref(false);

const onClearImage = () => props.clearImage?.();

const onFileChange = (e) => {
    const file = e.target?.files?.[0];
    if (!file) return;
    props.handleImageUpload?.({ files: [file] });
};

const onDragEnter = () => {
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    props.handleImageUpload?.({ files: [file] });
};

const kondisiFields = [
    { key: "bentuk_tanah_id", label: "Bentuk tanah", opts: "bentukTanahs" },
    { key: "posisi_tanah_id", label: "Posisi tanah", opts: "posisiTanahs" },
    { key: "kondisi_tanah_id", label: "Kondisi tanah", opts: "kondisiTanahs" },
    { key: "topografi_id", label: "Topografi", opts: "topografis" },
    { key: "dokumen_tanah_id", label: "Dokumen tanah", opts: "dokumenTanahs" },
    { key: "peruntukan_id", label: "Peruntukan", opts: "peruntukans" },
];
</script>

<template>
    <div class="space-y-6 p-4 sm:p-5">
        <UiSectionHeader
            title="Properti"
            subtitle="Foto, ukuran, kondisi legalitas, dan harga."
            icon="pi pi-building"
        />

        <UiSurface variant="inset" class="p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-balance text-sm font-semibold text-slate-900">
                        Foto properti <span v-if="isCreate" class="text-red-500" aria-hidden="true">*</span>
                    </p>
                    <p class="text-pretty text-xs text-slate-500">
                        Pilih file, drag &amp; drop ke area ini, atau paste dari clipboard.
                        <span v-if="!isCreate">Kosongkan bila tidak mengganti foto.</span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        for="pembanding-image"
                        class="inline-flex h-9 cursor-pointer items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <i class="pi pi-upload text-[12px] text-slate-500" aria-hidden="true" />
                        <span class="ml-2">Pilih file</span>
                    </label>
                    <input
                        id="pembanding-image"
                        type="file"
                        accept="image/*"
                        class="sr-only"
                        @change="onFileChange"
                    />
                </div>
            </div>

            <div
                class="mt-4 overflow-hidden rounded-[var(--radius-lg)] border border-slate-200 bg-white"
                :class="isDragging ? 'ring-2 ring-amber-200' : ''"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent="onDragEnter"
                @dragleave="onDragLeave"
                @drop.prevent="onDrop"
            >
                <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" alt="Preview foto properti" class="h-56 w-full object-cover" />
                    <button
                        type="button"
                        class="ui-hit absolute right-2 top-2 inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-2 text-slate-700 hover:bg-slate-50"
                        aria-label="Hapus foto"
                        @click="onClearImage"
                    >
                        <i class="pi pi-times text-[12px]" aria-hidden="true" />
                    </button>
                </div>
                <div v-else class="flex items-center justify-center p-8">
                    <div class="flex flex-col items-center gap-2 text-center">
                        <div class="flex size-14 items-center justify-center rounded-full bg-slate-100">
                            <i class="pi pi-image text-xl text-slate-300" aria-hidden="true" />
                        </div>
                        <p class="text-pretty text-xs font-medium text-slate-600">
                            Tarik file gambar ke sini, atau gunakan tombol “Pilih file”.
                        </p>
                        <p v-if="form.errors.image" class="text-pretty text-xs font-medium text-red-600">
                            {{ form.errors.image }}
                        </p>
                    </div>
                </div>
            </div>
        </UiSurface>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <UiField id="luas_tanah" label="Luas tanah" :required="true" :error="form.errors.luas_tanah">
                <InputNumber
                    v-model="form.luas_tanah"
                    inputId="luas_tanah"
                    v-bind="numConfig"
                    suffix=" m2"
                    placeholder="0"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>

            <UiField
                id="luas_bangunan"
                label="Luas bangunan"
                :required="bangunanRequired"
                :error="form.errors.luas_bangunan"
                :help="isTanah ? 'Tidak diperlukan untuk objek Tanah.' : ''"
            >
                <InputNumber
                    v-model="form.luas_bangunan"
                    inputId="luas_bangunan"
                    v-bind="numConfig"
                    suffix=" m2"
                    placeholder="0"
                    class="w-full filter-light ui-tabular"
                    :disabled="isTanah"
                />
            </UiField>

            <UiField id="lebar_depan" label="Lebar depan" :required="true" :error="form.errors.lebar_depan">
                <InputNumber
                    v-model="form.lebar_depan"
                    inputId="lebar_depan"
                    v-bind="numConfig"
                    suffix=" m"
                    placeholder="0"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>

            <UiField id="lebar_jalan" label="Lebar jalan" :required="true" :error="form.errors.lebar_jalan">
                <InputNumber
                    v-model="form.lebar_jalan"
                    inputId="lebar_jalan"
                    v-bind="numConfig"
                    suffix=" m"
                    placeholder="0"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>

            <UiField
                id="tahun_bangun"
                label="Tahun bangun"
                :required="bangunanRequired"
                :error="form.errors.tahun_bangun"
                :help="isTanah ? 'Tidak diperlukan untuk objek Tanah.' : ''"
            >
                <InputNumber
                    v-model="form.tahun_bangun"
                    inputId="tahun_bangun"
                    v-bind="numConfig"
                    placeholder="mis. 2010"
                    class="w-full filter-light ui-tabular"
                    :disabled="isTanah"
                />
            </UiField>

            <UiField id="rasio_tapak" label="Rasio tapak / FAR" :error="form.errors.rasio_tapak">
                <InputText
                    v-model="form.rasio_tapak"
                    id="rasio_tapak"
                    placeholder="mis. 0.6"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>
        </div>

        <UiSurface padding="none" class="overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <p class="text-balance text-sm font-semibold text-slate-900">Kondisi &amp; legalitas</p>
                <p class="mt-0.5 text-pretty text-xs text-slate-500">Pilih nilai master untuk memperjelas kondisi dan dokumen.</p>
            </div>
            <div class="p-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField
                        v-for="field in kondisiFields"
                        :key="field.key"
                        :id="field.key"
                        :label="field.label"
                        :required="true"
                        :error="form.errors[field.key]"
                    >
                        <Select
                            v-model="form[field.key]"
                            :options="options[field.opts] ?? []"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih..."
                            class="w-full filter-light"
                            :inputId="field.key"
                        />
                    </UiField>
                </div>
            </div>
        </UiSurface>

        <UiSurface variant="inset" class="p-4">
            <UiField id="harga" label="Harga" :required="true" :error="form.errors.harga" help="Harga penawaran atau transaksi.">
                <InputNumber
                    v-model="form.harga"
                    inputId="harga"
                    v-bind="currencyConfig"
                    placeholder="Rp 0"
                    class="w-full filter-light ui-tabular"
                />
            </UiField>
        </UiSurface>

        <div class="flex justify-between border-t border-slate-100 pt-4">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="emit('prev')" />
            <Button
                label="Lanjut ke Catatan"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="secondary"
                outlined
                @click="emit('next')"
            />
        </div>
    </div>
</template>

