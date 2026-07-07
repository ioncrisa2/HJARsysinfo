import { onBeforeUnmount, onMounted, ref } from "vue";

/**
 * Handle image file selection, drag-drop, paste, and cropper handoff.
 *
 * @param {{ onUploadReady?: Function }} options
 * @returns {{ isDragging: import("vue").Ref<boolean>, cropperVisible: import("vue").Ref<boolean>, cropperImageSrc: import("vue").Ref<string|null>, onFileChange: Function, onDragEnter: Function, onDragLeave: Function, onDrop: Function, onCropDone: Function }}
 */
export function useImageUploadCropper({ onUploadReady }) {
    const isDragging = ref(false);
    const cropperVisible = ref(false);
    const cropperImageSrc = ref(null);

    const processImageForCrop = (file) => {
        if (!file) return;

        const objectUrl = URL.createObjectURL(file);
        const img = new Image();

        img.onload = () => {
            const ratio = img.width / img.height;
            if (ratio < 1.3) {
                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");
                const targetWidth = Math.max(img.width, img.height * (16 / 9));
                const targetHeight = targetWidth * (9 / 16);
                canvas.width = targetWidth;
                canvas.height = targetHeight;

                ctx.filter = "blur(40px)";
                ctx.drawImage(img, -targetWidth * 0.2, -targetHeight * 0.2, targetWidth * 1.4, targetHeight * 1.4);
                ctx.filter = "none";
                ctx.fillStyle = "rgba(0,0,0,0.5)";
                ctx.fillRect(0, 0, targetWidth, targetHeight);

                const x = (targetWidth - img.width) / 2;
                const y = (targetHeight - img.height) / 2;
                ctx.drawImage(img, x, y, img.width, img.height);

                cropperImageSrc.value = canvas.toDataURL("image/jpeg", 0.9);
                URL.revokeObjectURL(objectUrl);
            } else {
                cropperImageSrc.value = objectUrl;
            }

            cropperVisible.value = true;
        };

        img.src = objectUrl;
    };

    const onCropDone = ({ blob }) => {
        const file = new File([blob], "cropped_image.jpg", { type: "image/jpeg" });
        onUploadReady?.(file);
    };

    const onFileChange = (event) => {
        const file = event.target?.files?.[0];
        if (!file) return;

        processImageForCrop(file);
        event.target.value = "";
    };

    const onDragEnter = () => {
        isDragging.value = true;
    };

    const onDragLeave = () => {
        isDragging.value = false;
    };

    const onDrop = (event) => {
        isDragging.value = false;
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;

        processImageForCrop(file);
    };

    const handlePaste = (event) => {
        const items = event.clipboardData?.items;
        if (!items) return;

        for (const item of items) {
            if (!item.type.startsWith("image/")) continue;
            const file = item.getAsFile();
            if (!file) continue;

            processImageForCrop(file);
            break;
        }
    };

    onMounted(() => {
        if (typeof window !== "undefined") {
            window.addEventListener("paste", handlePaste);
        }
    });

    onBeforeUnmount(() => {
        if (typeof window !== "undefined") {
            window.removeEventListener("paste", handlePaste);
        }
    });

    return {
        isDragging,
        cropperVisible,
        cropperImageSrc,
        onFileChange,
        onDragEnter,
        onDragLeave,
        onDrop,
        onCropDone,
    };
}
