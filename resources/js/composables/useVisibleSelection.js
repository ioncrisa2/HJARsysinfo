import { computed, ref, unref, watch } from "vue";

/**
 * Manage selected IDs against the currently visible rows.
 *
 * @param {import("vue").Ref|Function|Array} visibleIdsSource
 * @param {{ pruneOnChange?: boolean }} options
 * @returns {{ selectedIds: import("vue").Ref<Array>, allVisibleSelected: import("vue").ComputedRef<boolean>, toggleSelected: Function, toggleAllVisible: Function, clearSelection: Function }}
 */
export function useVisibleSelection(visibleIdsSource, options = {}) {
    const selectedIds = ref([]);
    const pruneOnChange = options.pruneOnChange ?? true;
    const getVisibleIds = () => {
        const value = typeof visibleIdsSource === "function" ? visibleIdsSource() : unref(visibleIdsSource);
        return Array.isArray(value) ? value : [];
    };

    const visibleIds = computed(getVisibleIds);
    const allVisibleSelected = computed(() => (
        visibleIds.value.length > 0 &&
        visibleIds.value.every((id) => selectedIds.value.includes(id))
    ));

    const toggleSelected = (id) => {
        selectedIds.value = selectedIds.value.includes(id)
            ? selectedIds.value.filter((selectedId) => selectedId !== id)
            : [...selectedIds.value, id];
    };

    const toggleAllVisible = () => {
        selectedIds.value = allVisibleSelected.value
            ? selectedIds.value.filter((id) => !visibleIds.value.includes(id))
            : [...new Set([...selectedIds.value, ...visibleIds.value])];
    };

    const clearSelection = () => {
        selectedIds.value = [];
    };

    if (pruneOnChange) {
        watch(visibleIds, (ids) => {
            selectedIds.value = selectedIds.value.filter((id) => ids.includes(id));
        });
    }

    return {
        selectedIds,
        allVisibleSelected,
        toggleSelected,
        toggleAllVisible,
        clearSelection,
    };
}
