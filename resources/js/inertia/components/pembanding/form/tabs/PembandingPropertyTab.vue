<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import FileUpload from "primevue/fileupload";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Select from "primevue/select";

const props = defineProps({
    form:             { type: Object,   required: true },
    options:          { type: Object,   default: () => ({}) },
    mode:             { type: String,   default: "create" },
    imagePreview:     { type: String,   default: null },
    isTanah:          { type: Boolean,  default: false },
    bangunanRequired: { type: Boolean,  default: true },
    numConfig:        { type: Object,   default: () => ({}) },
    currencyConfig:   { type: Object,   default: () => ({}) },
    handleImageUpload:{ type: Function, default: null },
    clearImage:       { type: Function, default: null },
});

const emit = defineEmits(["prev", "next"]);

const isCreate  = props.mode === "create";
const isDragging = ref(false);

const onUpload     = (event) => props.handleImageUpload?.(event);
const onClearImage = () => props.clearImage?.();

const onDragEnter = () => { isDragging.value = true; };
const onDragLeave = () => { isDragging.value = false; };
const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && props.handleImageUpload) props.handleImageUpload({ files: [file] });
};

const kondisiFields = [
    { key: "bentuk_tanah_id",  label: "Bentuk Tanah",  opts: "bentukTanahs"  },
    { key: "posisi_tanah_id",  label: "Posisi Tanah",  opts: "posisiTanahs"  },
    { key: "kondisi_tanah_id", label: "Kondisi Tanah", opts: "kondisiTanahs" },
    { key: "topografi_id",     label: "Topografi",     opts: "topografis"    },
    { key: "dokumen_tanah_id", label: "Dokumen Tanah", opts: "dokumenTanahs" },
    { key: "peruntukan_id",    label: "Peruntukan",    opts: "peruntukans"   },
];
</script>

<template>
    <div class="space-y-6 p-5">

        <!-- ── Foto Properti ──────────────────────────── -->
        <section>
            <div class="mb-3 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-camera text-amber-600" style="font-size: 12px" />
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-700">
                        Foto Properti <span v-if="isCreate" class="text-red-400">*</span>
                    </h2>
                    <p class="text-[11px] text-slate-400">
                        <template v-if="isCreate">
                            Pilih file, drag &amp; drop, atau tekan
                            <kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[11px] shadow-sm">Ctrl+V</kbd>
                            untuk tempel dari clipboard.
                        </template>
                        <template v-else>
                            Biarkan kosong untuk mempertahankan foto saat ini.
                        </template>
                    </p>
                </div>
            </div>

            <!-- Preview -->
            <div v-if="imagePreview" class="relative overflow-hidden rounded-xl border border-slate-200">
                <img :src="imagePreview" alt="Preview" class="h-56 w-full object-cover transition-transform duration-500 hover:scale-105" />
                <button
                    type="button"
                    class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-slate-500 shadow transition-colors hover:bg-white hover:text-red-500"
                    @click="onClearImage"
                >
                    <i class="pi pi-times text-xs" />
                </button>
                <div
                    v-if="!isCreate"
                    class="absolute bottom-2 left-2 rounded-full px-2.5 py-0.5 text-[10px] font-semibold text-white shadow"
                    :class="form.image ? 'bg-amber-500' : 'bg-slate-600/70'"
                >
                    {{ form.image ? "Foto baru dipilih" : "Foto saat ini" }}
                </div>
            </div>

            <!-- Drop zone -->
            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed py-12 text-center transition-all duration-200"
                :class="isDragging
                    ? 'scale-[0.995] border-amber-400 bg-amber-50/60'
                    : 'border-slate-200 bg-slate-50 hover:border-amber-300 hover:bg-amber-50/30'"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent="onDragEnter"
                @dragleave="onDragLeave"
                @drop.prevent="onDrop"
            >
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full transition-colors"
                    :class="isDragging ? 'bg-amber-100' : 'bg-slate-100'"
                >
                    <i
                        class="pi pi-cloud-upload text-2xl transition-colors"
                        :class="isDragging ? 'text-amber-500' : 'text-slate-400'"
                    />
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="isDragging ? 'text-amber-700' : 'text-slate-600'">
                        {{ isDragging ? "Lepaskan untuk mengunggah" : isCreate ? "Belum ada foto dipilih" : "Ganti foto properti" }}
                    </p>
                    <p class="text-xs text-slate-400">Seret file ke sini atau tekan Ctrl+V</p>
                </div>
                <FileUpload
                    name="image"
                    accept="image/*"
                    custom-upload
                    auto
                    :max-file-size="15000000"
                    choose-label="Pilih dari File"
                    @uploader="onUpload"
                />
            </div>

            <p v-if="form.errors.image" class="mt-2 flex items-center gap-1 text-xs text-red-500">
                <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.image }}
            </p>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Dimensi ─────────────────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-expand text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Dimensi</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Luas Tanah <span class="text-red-400">*</span>
                    </label>
                    <InputNumber v-model="form.luas_tanah" v-bind="numConfig" suffix=" m²" placeholder="0" class="w-full" />
                    <p v-if="form.errors.luas_tanah" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.luas_tanah }}
                    </p>
                </div>

                <div class="space-y-1.5" :class="isTanah ? 'opacity-60' : ''">
                    <label class="text-xs font-semibold text-slate-500">
                        Luas Bangunan
                        <span v-if="bangunanRequired" class="text-red-400">*</span>
                        <span v-else class="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <InputNumber v-model="form.luas_bangunan" v-bind="numConfig" suffix=" m²" placeholder="0" class="w-full" :disabled="isTanah" />
                    <p v-if="isTanah" class="text-[11px] text-slate-400">Tidak diperlukan untuk objek Tanah.</p>
                    <p v-if="form.errors.luas_bangunan" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.luas_bangunan }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Lebar Depan <span class="text-red-400">*</span>
                    </label>
                    <InputNumber v-model="form.lebar_depan" v-bind="numConfig" suffix=" m" placeholder="0" class="w-full" />
                    <p v-if="form.errors.lebar_depan" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.lebar_depan }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        Lebar Jalan <span class="text-red-400">*</span>
                    </label>
                    <InputNumber v-model="form.lebar_jalan" v-bind="numConfig" suffix=" m" placeholder="0" class="w-full" />
                    <p v-if="form.errors.lebar_jalan" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.lebar_jalan }}
                    </p>
                </div>

                <div class="space-y-1.5" :class="isTanah ? 'opacity-60' : ''">
                    <label class="text-xs font-semibold text-slate-500">
                        Tahun Bangun
                        <span v-if="bangunanRequired" class="text-red-400">*</span>
                        <span v-else class="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <InputNumber v-model="form.tahun_bangun" v-bind="numConfig" placeholder="mis. 2010" class="w-full" :disabled="isTanah" />
                    <p v-if="isTanah" class="text-[11px] text-slate-400">Tidak diperlukan untuk objek Tanah.</p>
                    <p v-if="form.errors.tahun_bangun" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.tahun_bangun }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">Rasio Tapak / FAR</label>
                    <InputText v-model="form.rasio_tapak" placeholder="mis. 0.6" class="w-full" />
                </div>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Kondisi & Legalitas ─────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-file-check text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Kondisi &amp; Legalitas</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="field in kondisiFields" :key="field.key" class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500">
                        {{ field.label }} <span class="text-red-400">*</span>
                    </label>
                    <Select
                        v-model="form[field.key]"
                        :options="options[field.opts] ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih..."
                        class="w-full"
                    />
                    <p v-if="form.errors[field.key]" class="flex items-center gap-1 text-xs text-red-500">
                        <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors[field.key] }}
                    </p>
                </div>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Harga ──────────────────────────────────── -->
        <section>
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-tag text-amber-600" style="font-size: 12px" />
                </div>
                <h2 class="text-sm font-bold text-slate-700">Harga</h2>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                <label class="mb-1.5 block text-xs font-semibold text-amber-800">
                    Harga Penawaran / Transaksi <span class="text-red-400">*</span>
                </label>
                <InputNumber
                    v-model="form.harga"
                    v-bind="currencyConfig"
                    placeholder="Rp 0"
                    class="w-full"
                />
                <p v-if="form.errors.harga" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                    <i class="pi pi-exclamation-circle text-[10px]" />{{ form.errors.harga }}
                </p>
            </div>
        </section>

        <!-- Nav -->
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
