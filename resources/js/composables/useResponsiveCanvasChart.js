import { nextTick, onBeforeUnmount, onMounted } from "vue";

export const useResponsiveCanvasChart = (canvasRef, draw, config = {}) => {
    const listenResize = config.listenResize ?? true;

    const render = () => {
        const canvas = canvasRef.value;
        if (!canvas) return;

        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        const rect = canvas.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;
        if (width <= 0 || height <= 0) return;

        const dpr = (typeof window !== "undefined" ? window.devicePixelRatio : 1) || 1;
        const pixelWidth = Math.max(1, Math.round(width * dpr));
        const pixelHeight = Math.max(1, Math.round(height * dpr));

        if (canvas.width !== pixelWidth || canvas.height !== pixelHeight) {
            canvas.width = pixelWidth;
            canvas.height = pixelHeight;
        }

        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        draw({ canvas, ctx, width, height, dpr });
    };

    const renderNextTick = () => nextTick(() => render());
    const handleResize = () => render();

    onMounted(() => {
        renderNextTick();
        if (listenResize && typeof window !== "undefined") {
            window.addEventListener("resize", handleResize);
        }
    });

    onBeforeUnmount(() => {
        if (listenResize && typeof window !== "undefined") {
            window.removeEventListener("resize", handleResize);
        }
    });

    return {
        render,
        renderNextTick,
    };
};
