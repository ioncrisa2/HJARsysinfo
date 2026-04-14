import { onBeforeUnmount, ref, unref, watch } from "vue";

/**
 * Adds a short "show delay" and a minimum visible duration for spinners/skeletons
 * to avoid flicker on fast responses.
 */
export function useMinLoadingState(loadingSource, options = {}) {
    const delayMs = Number(options.delayMs ?? 200);
    const minMs = Number(options.minMs ?? 350);

    const show = ref(false);
    let delayTimer = null;
    let minTimer = null;
    let shownAt = 0;

    const clearTimers = () => {
        if (delayTimer) {
            clearTimeout(delayTimer);
            delayTimer = null;
        }
        if (minTimer) {
            clearTimeout(minTimer);
            minTimer = null;
        }
    };

    watch(
        () => (typeof loadingSource === "function" ? loadingSource() : unref(loadingSource)),
        (isLoading) => {
            clearTimers();

            if (isLoading) {
                show.value = false;
                delayTimer = setTimeout(() => {
                    show.value = true;
                    shownAt = Date.now();
                }, Math.max(0, delayMs));
                return;
            }

            if (!show.value) {
                show.value = false;
                return;
            }

            const elapsed = Date.now() - shownAt;
            const remaining = Math.max(0, minMs - elapsed);
            if (remaining === 0) {
                show.value = false;
                return;
            }

            minTimer = setTimeout(() => {
                show.value = false;
            }, remaining);
        },
        { immediate: true }
    );

    onBeforeUnmount(() => {
        clearTimers();
    });

    return { show };
}

