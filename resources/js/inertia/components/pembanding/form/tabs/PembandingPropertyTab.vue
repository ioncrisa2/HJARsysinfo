<script setup>
import { ref } from "vue";
import { computed } from "vue";
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

const isSewa = computed(() => {
    const listingId = props.form.jenis_listing_id;
    if (!listingId || !props.options?.jenisListings) return false;
    const listing = props.options.jenisListings.find(l => l.value == listingId);
    return listing?.label?.toLowerCase() === 'sewa';
});

const rentPeriodLabel = computed(() => {
    if (!isSewa.value || !props.form.jangka_waktu_sewa || !props.form.satuan_waktu_sewa) {
        return null;
    }

    const duration = Number(props.form.jangka_waktu_sewa);
    const unit = String(props.form.satuan_waktu_sewa).toLowerCase();

    if (duration === 1 && unit === "bulan") return "per bulan";
    if (duration === 1 && unit === "tahun") return "per tahun";

    return `per ${duration} ${unit}`;
});
</script>

<template>
    <div class="space-y-8 p-6 sm:p-8">
        <UiSectionHeader
            title="Spesifikasi Properti"
            subtitle="Detail teknis bangunan, karakteristik tanah, legalitas, dan informasi harga."
            icon="pi pi-building"
        />

        <!-- Image Upload Section -->
        <UiSurface variant="inset" class="p-6 bg-slate-50 rounded-2xl border border-slate-200 border-dashed">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                <div class="space-y-1">
                    <p class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="pi pi-image text-slate-400" />
                        Foto Properti <span v-if="isCreate" class="text-red-500">*</span>
                    </p>
                    <p class="text-xs text-slate-500">
                        Format: JPG, PNG. Maks: 5MB.
                        <span v-if="!isCreate" class="text-amber-600 font-medium">Kosongkan bila tidak ingin mengganti foto.</span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        for="pembanding-image"
                        class="inline-flex h-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm"
                    >
                        <i class="pi pi-upload text-[11px]" aria-hidden="true" />
                        <span class="ml-2">Pilih Foto</span>
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
                class="relative overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition-all duration-300 group"
                :class="isDragging ? 'border-amber-400 bg-amber-50/50' : 'border-slate-100'"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent="onDragEnter"
                @dragleave="onDragLeave"
                @drop.prevent="onDrop"
            >
                <div v-if="imagePreview" class="relative group">
                    <img :src="imagePreview" alt="Preview foto properti" class="h-64 w-full object-cover group-hover:scale-105 transition-transform duration-700" />
                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <button
                            type="button"
                            class="bg-white/90 text-red-600 p-2 rounded-full shadow-lg hover:bg-white hover:scale-110 transition-all"
                            aria-label="Hapus foto"
                            @click="onClearImage"
                        >
                            <i class="pi pi-trash" />
                        </button>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center p-12 text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-slate-100 mb-4 group-hover:scale-110 transition-transform">
                        <i class="pi pi-cloud-upload text-2xl text-slate-400" />
                    </div>
                    <p class="text-sm font-bold text-slate-700 mb-1">Drag & Drop foto di sini</p>
                    <p class="text-xs text-slate-400">Atau gunakan tombol di atas untuk memilih file.</p>
                    <p v-if="form.errors.image" class="mt-3 text-xs font-bold text-red-500">
                        <i class="pi pi-exclamation-circle" /> {{ form.errors.image }}
                    </p>
                </div>
            </div>
        </UiSurface>

        <!-- Technical Specs -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <UiField id="luas_tanah" label="Luas Tanah" :required="true" :error="form.errors.luas_tanah">
                <InputNumber
                    v-model="form.luas_tanah"
                    inputId="luas_tanah"
                    v-bind="numConfig"
                    suffix=" m²"
                    placeholder="0"
                    class="w-full rounded-xl bg-slate-50/50"
                />
            </UiField>

            <UiField
                id="luas_bangunan"
                label="Luas Bangunan"
                :required="bangunanRequired"
                :error="form.errors.luas_bangunan"
                :help="isTanah ? 'Tidak perlu diisi untuk objek Tanah.' : ''"
            >
                <InputNumber
                    v-model="form.luas_bangunan"
                    inputId="luas_bangunan"
                    v-bind="numConfig"
                    suffix=" m²"
                    placeholder="0"
                    class="w-full rounded-xl bg-slate-50/50"
                    :disabled="isTanah"
                />
            </UiField>

            <UiField id="lebar_depan" label="Lebar Depan" :required="true" :error="form.errors.lebar_depan">
                <InputNumber
                    v-model="form.lebar_depan"
                    inputId="lebar_depan"
                    v-bind="numConfig"
                    suffix=" m"
                    placeholder="0"
                    class="w-full rounded-xl bg-slate-50/50"
                />
            </UiField>

            <UiField id="lebar_jalan" label="Lebar Jalan" :required="true" :error="form.errors.lebar_jalan">
                <InputNumber
                    v-model="form.lebar_jalan"
                    inputId="lebar_jalan"
                    v-bind="numConfig"
                    suffix=" m"
                    placeholder="0"
                    class="w-full rounded-xl bg-slate-50/50"
                />
            </UiField>

            <UiField
                id="tahun_bangun"
                label="Tahun Bangun"
                :required="bangunanRequired"
                :error="form.errors.tahun_bangun"
                :help="isTanah ? 'Tidak perlu diisi untuk objek Tanah.' : ''"
            >
                <InputNumber
                    v-model="form.tahun_bangun"
                    inputId="tahun_bangun"
                    v-bind="numConfig"
                    placeholder="mis. 2010"
                    class="w-full rounded-xl bg-slate-50/50"
                    :disabled="isTanah"
                />
            </UiField>

            <UiField id="rasio_tapak" label="Rasio Tapak / FAR" :error="form.errors.rasio_tapak">
                <InputText
                    v-model="form.rasio_tapak"
                    id="rasio_tapak"
                    placeholder="mis. 0.6"
                    class="w-full rounded-xl bg-slate-50/50 border-slate-200"
                />
            </UiField>
        </div>

        <div class="border-t border-slate-100 pt-8">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Karakteristik & Legalitas</h3>
            <div class="grid gap-6 md:grid-cols-2">
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
                        placeholder="Pilih nilai..."
                        class="w-full rounded-xl bg-slate-50/50"
                        :inputId="field.key"
                        filter
                    />
                </UiField>
            </div>
        </div>

        <!-- Pricing Section -->
        <div class="border-t border-slate-100 pt-8">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Nilai Properti</h3>
            <div class="p-6 rounded-2xl bg-slate-900 text-white shadow-xl shadow-slate-200">
                <div class="grid gap-6 md:grid-cols-3">
                    <UiField id="harga" :label="isSewa ? 'Harga Sewa' : 'Harga Penawaran/Transaksi'" :required="true" :error="form.errors.harga" :help="isSewa ? 'Nominal harga sewa untuk periode di samping: per bulan, per beberapa bulan, atau per tahun.' : 'Nilai total properti.'">
                        <InputNumber
                            v-model="form.harga"
                            inputId="harga"
                            v-bind="currencyConfig"
                            placeholder="Rp 0"
                            class="w-full rounded-xl bg-slate-800 border-slate-700 text-white font-bold"
                        />
                    </UiField>
                    
                    <template v-if="isSewa">
                        <UiField id="jangka_waktu_sewa" label="Periode Harga Sewa" :required="true" :error="form.errors.jangka_waktu_sewa" help="Isi 1 untuk per bulan/per tahun, atau beberapa bulan seperti 3, 6, 12.">
                            <InputNumber
                                v-model="form.jangka_waktu_sewa"
                                inputId="jangka_waktu_sewa"
                                v-bind="numConfig"
                                placeholder="1"
                                class="w-full rounded-xl bg-slate-800 border-slate-700 text-white"
                            />
                        </UiField>

                        <UiField id="satuan_waktu_sewa" label="Satuan" :required="true" :error="form.errors.satuan_waktu_sewa">
                            <Select
                                v-model="form.satuan_waktu_sewa"
                                :options="[{label:'Bulan', value:'Bulan'}, {label:'Tahun', value:'Tahun'}]"
                                option-label="label"
                                option-value="value"
                                placeholder="Pilih..."
                                class="w-full rounded-xl bg-slate-800 border-slate-700 text-white"
                                inputId="satuan_waktu_sewa"
                            />
                        </UiField>

                        <div v-if="rentPeriodLabel" class="md:col-span-3 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm font-semibold text-amber-100">
                            Harga sewa akan disimpan sebagai {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(form.harga || 0) }} {{ rentPeriodLabel }}.
                        </div>
                    </template>
                    
                    <div v-else-if="form.harga && form.luas_tanah" class="flex flex-col justify-end pb-1.5">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Estimasi per m²</p>
                        <p class="text-lg font-bold text-amber-400">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(form.harga / form.luas_tanah) }}<span class="text-slate-500 text-xs font-normal"> /m²</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between border-t border-slate-100 pt-6">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined class="rounded-xl px-6" @click="emit('prev')" />
            <Button
                label="Lanjut ke Catatan"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="primary"
                class="rounded-xl px-6"
                @click="emit('next')"
            />
        </div>
    </div>
</template>
