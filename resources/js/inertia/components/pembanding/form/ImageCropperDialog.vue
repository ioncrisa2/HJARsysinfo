<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const props = defineProps({
    visible: { type: Boolean, default: false },
    imageSrc: { type: String, default: null }
});

const emit = defineEmits(['update:visible', 'crop']);

const imageRef = ref(null);
let cropperInstance = null;

const initCropper = () => {
    if (cropperInstance) {
        cropperInstance.destroy();
    }
    if (imageRef.value) {
        cropperInstance = new Cropper(imageRef.value, {
            aspectRatio: 16 / 9,
            viewMode: 1, // Restrict crop box to not exceed the size of the canvas
            dragMode: 'move', // Allow moving the image inside the crop box
            autoCropArea: 1, // Cover the entire 16:9 area by default
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    }
};

watch(() => props.visible, (newVal) => {
    if (newVal) {
        // Init cropper slightly after modal appears
        setTimeout(() => {
            initCropper();
        }, 100);
    } else {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    }
});

watch(() => props.imageSrc, (newVal) => {
    if (newVal && props.visible) {
        setTimeout(() => {
            initCropper();
        }, 100);
    }
});

onBeforeUnmount(() => {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
});

const onCancel = () => {
    emit('update:visible', false);
};

const onSave = () => {
    if (!cropperInstance) return;
    
    const canvas = cropperInstance.getCroppedCanvas({
        width: 1280,
        height: 720,
        fillColor: '#fff',
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    // Convert to Blob and emit
    canvas.toBlob((blob) => {
        if (!blob) return;
        const resultUrl = URL.createObjectURL(blob);
        emit('crop', { blob, dataUrl: resultUrl });
        emit('update:visible', false);
    }, 'image/jpeg', 0.9);
};
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        modal
        header="Sesuaikan Area Foto"
        :style="{ width: '800px', maxWidth: '95vw' }"
        :closable="true"
        :dismissableMask="false"
        class="cropper-dialog"
    >
        <div class="relative w-full overflow-hidden bg-slate-900 rounded-lg shadow-inner flex items-center justify-center min-h-[50vh] max-h-[70vh]">
            <img 
                v-if="imageSrc" 
                ref="imageRef" 
                :src="imageSrc" 
                alt="Image to crop" 
                class="block max-w-full"
                crossorigin="anonymous"
            />
            <div v-else class="text-white p-8">Memuat gambar...</div>
        </div>

        <div class="flex justify-between items-center mt-6 p-2 bg-slate-50 rounded-xl border border-slate-100">
            <div class="text-xs text-slate-500 px-2 flex items-center gap-2">
                <i class="pi pi-info-circle text-amber-500"></i>
                <span class="hidden sm:inline">Geser gambar dan sesuaikan zoom untuk area yang diinginkan. Rasio otomatis 16:9.</span>
                <span class="inline sm:hidden">Rasio terkunci 16:9.</span>
            </div>
            <div class="flex gap-2">
                <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="onCancel" />
                <Button label="Simpan Potongan" icon="pi pi-check" severity="primary" class="rounded-xl px-4" @click="onSave" />
            </div>
        </div>
    </Dialog>
</template>

<style>
/* Adjust cropper styles if needed to fit the modal nicely */
.cropper-dialog .p-dialog-content {
    padding-bottom: 1.5rem;
}
.cropper-view-box, .cropper-face {
    border-radius: 4px;
}
.cropper-modal {
    opacity: 0.8;
}
</style>
