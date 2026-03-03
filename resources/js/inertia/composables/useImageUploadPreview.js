import { onBeforeUnmount, onMounted, ref } from "vue";

export const useImageUploadPreview = (target, config = {}) => {
    const imageKey = config.imageKey ?? "image";
    const imagePreview = ref(config.initialPreview ?? null);
    const enablePaste = config.enablePaste ?? true;

    const applyImageFile = (file) => {
        if (!file) return;

        target[imageKey] = file;

        if (typeof FileReader === "undefined") {
            imagePreview.value = null;
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target?.result ?? null;
        };
        reader.readAsDataURL(file);
    };

    const handleImageUpload = (event) => {
        const file = event?.files?.[0] ?? null;
        applyImageFile(file);
    };

    const clearImage = () => {
        target[imageKey] = null;
        imagePreview.value = null;
    };

    const handlePaste = (event) => {
        const items = event.clipboardData?.items;
        if (!items) return;

        for (const item of items) {
            if (!item.type.startsWith("image/")) continue;

            const file = item.getAsFile();
            if (!file) continue;

            applyImageFile(file);
            break;
        }
    };

    if (enablePaste) {
        onMounted(() => {
            window.addEventListener("paste", handlePaste);
        });

        onBeforeUnmount(() => {
            window.removeEventListener("paste", handlePaste);
        });
    }

    return {
        imagePreview,
        handleImageUpload,
        clearImage,
        handlePaste,
    };
};
