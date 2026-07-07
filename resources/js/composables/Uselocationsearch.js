import { ref } from "vue";

/**
 * useLocationSearch
 *
 * A small composable that wraps a location loader function with:
 * - debounced search (default 350ms)
 * - a loading flag scoped to just this level
 * - an explicit trigger for immediate (non-debounced) calls
 *
 * @param {Function} loaderFn  async (params) => void  — your loadRegencies / loadDistricts etc.
 * @param {number}   delay     debounce delay in ms (default 350)
 */
export function useLocationSearch(loaderFn, delay = 350) {
    const query = ref("");
    const loading = ref(false);

    let debounceTimer = null;

    /**
     * Call the loader immediately (e.g. when a parent filter changes).
     * Cancels any pending debounced call.
     */
    const load = async (params = {}) => {
        clearTimeout(debounceTimer);
        loading.value = true;
        try {
            await loaderFn({ q: query.value, ...params });
        } finally {
            loading.value = false;
        }
    };

    /**
     * Debounced version — call this from a search input's @input or @update:modelValue.
     */
    const search = (params = {}) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => load(params), delay);
    };

    /**
     * Clear the query and reload immediately.
     */
    const clear = (params = {}) => {
        query.value = "";
        load(params);
    };

    return {
        query,
        loading,
        load,
        search,
        clear,
    };
}
