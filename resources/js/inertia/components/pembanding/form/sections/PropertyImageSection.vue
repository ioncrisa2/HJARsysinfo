<script setup>
import UiSurface from "../../../ui/UiSurface.vue";
import ImageCropperDialog from "../ImageCropperDialog.vue";
import { useImageUploadCropper } from "../../../../composables/useImageUploadCropper";

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: "create" },
    imagePreview: { type: String, default: null },
});

const emit = defineEmits(["upload", "clear"]);

const isCreate = props.mode === "create";

const {
    isDragging,
    cropperVisible,
    cropperImageSrc,
    onFileChange,
    onDragEnter,
    onDragLeave,
    onDrop,
    onCropDone,
} = useImageUploadCropper({
    onUploadReady: (file) => emit("upload", { files: [file] }),
});

const onClearImage = () => emit("clear");
</script>

<template>
    <UiSurface variant="inset" class="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200 border-dashed">
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
            <div v-if="imagePreview" class="relative group w-full aspect-video">
                <img :src="imagePreview" alt="Preview foto properti" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                    <label
                        for="pembanding-image"
                        class="bg-white/90 text-slate-700 p-3 rounded-full shadow-lg hover:bg-white hover:scale-110 transition-all cursor-pointer"
                        title="Ganti Foto"
                    >
                        <i class="pi pi-sync" />
                    </label>
                    <button
                        v-if="cropperImageSrc"
                        type="button"
                        class="bg-white/90 text-blue-600 p-3 rounded-full shadow-lg hover:bg-white hover:scale-110 transition-all"
                        title="Edit Crop"
                        @click="cropperVisible = true"
                    >
                        <i class="pi pi-pencil" />
                    </button>
                    <button
                        type="button"
                        class="bg-white/90 text-red-600 p-3 rounded-full shadow-lg hover:bg-white hover:scale-110 transition-all"
                        title="Hapus foto"
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

        <ImageCropperDialog
            v-model:visible="cropperVisible"
            :image-src="cropperImageSrc"
            @crop="onCropDone"
        />
    </UiSurface>
</template>
