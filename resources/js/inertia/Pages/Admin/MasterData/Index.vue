<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import { useConfirm } from "primevue/useconfirm";

import MasterDataSidebar from "../../../components/admin/master-data/MasterDataSidebar.vue";
import MasterDataFilter from "../../../components/admin/master-data/MasterDataFilter.vue";
import MasterDataTable from "../../../components/admin/master-data/MasterDataTable.vue";
import MasterDataForm from "../../../components/admin/master-data/MasterDataForm.vue";
import { useDebouncedWatch } from "../../../composables/useDebouncedWatch";
import { useVisibleSelection } from "../../../composables/useVisibleSelection";

const props = defineProps({
    currentResource: { type: String, default: null },
    label: { type: String, default: "Master Data" },
    records: { type: Object, default: () => ({ data: [], links: [] }) },
    resources: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    supportsBadgeColor: { type: Boolean, default: false },
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const resourceUrl = (resource = props.currentResource) => `/admin/master-data/${resource}`;

const filterState = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    sort_by: props.filters?.sort_by ?? "sort_order",
    sort_dir: props.filters?.sort_dir ?? "asc",
    per_page: props.filters?.per_page ?? 20,
});

const rows = ref([...(props.records?.data ?? [])]);
const visibleIds = computed(() => rows.value.map((row) => row.id));
const {
    selectedIds,
    allVisibleSelected,
    toggleSelected: toggleVisibleSelected,
    toggleAllVisible: toggleAllVisibleSelection,
    clearSelection,
} = useVisibleSelection(visibleIds);
const showForm = ref(false);
const editingRecord = ref(null);
const draggedId = ref(null);
const savingOrder = ref(false);
const deletingBulk = ref(false);

const form = useForm({
    name: "",
    sort_order: null,
    is_active: true,
    badge_color: "#64748b",
});

const hasOrderChanges = computed(() => {
    const original = (props.records?.data ?? []).map((row) => row.id).join(",");
    const current = rows.value.map((row) => row.id).join(",");
    return original !== current;
});

const canReorder = computed(() => {
    return props.can.reorder && props.currentResource && filterState.sort_by === "sort_order" && filterState.sort_dir === "asc";
});

watch(
    () => props.records?.data,
    (data) => {
        rows.value = [...(data ?? [])];
    },
    { deep: true },
);

watch(
    () => props.filters,
    (filters) => {
        filterState.search = filters?.search ?? "";
        filterState.status = filters?.status ?? "all";
        filterState.sort_by = filters?.sort_by ?? "sort_order";
        filterState.sort_dir = filters?.sort_dir ?? "asc";
        filterState.per_page = filters?.per_page ?? 20;
    },
    { deep: true },
);

useDebouncedWatch(() => filterState.search, () => {
    if (!props.currentResource) return;
    applyFilters();
}, { delay: 300 });

const buildFilterParams = () => {
    const params = {};
    const search = `${filterState.search ?? ""}`.trim();

    if (search) params.search = search;
    if (filterState.status !== "all") params.status = filterState.status;
    if (filterState.sort_by !== "sort_order") params.sort_by = filterState.sort_by;
    if (filterState.sort_dir !== "asc") params.sort_dir = filterState.sort_dir;
    if (filterState.per_page !== 20) params.per_page = filterState.per_page;

    return params;
};

function applyFilters() {
    if (!props.currentResource) return;

    router.get(resourceUrl(), buildFilterParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const resetFilters = () => {
    filterState.search = "";
    filterState.status = "all";
    filterState.sort_by = "sort_order";
    filterState.sort_dir = "asc";
    filterState.per_page = 20;
    applyFilters();
};

const openCreate = () => {
    if (!props.can.create) return;

    editingRecord.value = null;
    form.reset();
    form.clearErrors();
    form.name = "";
    form.sort_order = null;
    form.is_active = true;
    form.badge_color = "#64748b";
    showForm.value = true;
};

const openEdit = (record) => {
    if (!props.can.update) return;

    editingRecord.value = record;
    form.clearErrors();
    form.name = record.name ?? "";
    form.sort_order = record.sort_order ?? 0;
    form.is_active = Boolean(record.is_active);
    form.badge_color = record.badge_color || "#64748b";
    showForm.value = true;
};

const submit = () => {
    if ((editingRecord.value && !props.can.update) || (!editingRecord.value && !props.can.create)) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => (showForm.value = false),
    };

    if (editingRecord.value) {
        form.put(`${resourceUrl()}/${editingRecord.value.id}`, options);
        return;
    }

    form.post(resourceUrl(), options);
};

const deleteRecord = (record) => {
    if (!props.can.delete) return;

    confirm.require({
        message: `Hapus "${record.name}"? Tindakan ini mungkin gagal jika data masih digunakan.`,
        header: "Konfirmasi Hapus",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        acceptLabel: "Hapus",
        rejectLabel: "Batal",
        accept: () => router.delete(`${resourceUrl()}/${record.id}`, { preserveScroll: true }),
    });
};

const toggleStatus = (record) => {
    if (!props.can.toggleStatus) return;

    router.patch(`${resourceUrl()}/${record.id}/toggle-status`, {}, { preserveScroll: true });
};

const toggleAllVisible = () => {
    if (!props.can.deleteAny) return;

    toggleAllVisibleSelection();
};

const toggleSelected = (id) => {
    if (!props.can.deleteAny) return;

    toggleVisibleSelected(id);
};

const bulkDelete = () => {
    if (!props.can.deleteAny || selectedIds.value.length === 0) return;

    confirm.require({
        message: `Hapus ${selectedIds.value.length} data terpilih? Data yang masih digunakan bisa membuat proses gagal.`,
        header: "Konfirmasi Bulk Delete",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        acceptLabel: "Hapus",
        rejectLabel: "Batal",
        accept: () => {
            deletingBulk.value = true;
            router.post(
                `${resourceUrl()}/bulk-delete`,
                { ids: selectedIds.value },
                {
                    preserveScroll: true,
                    onFinish: () => (deletingBulk.value = false),
                    onSuccess: clearSelection,
                },
            );
        },
    });
};

const onDragStart = (record) => {
    if (!canReorder.value) return;
    draggedId.value = record.id;
};

const onDrop = (targetRecord) => {
    if (!canReorder.value || draggedId.value === null || draggedId.value === targetRecord.id) {
        draggedId.value = null;
        return;
    }

    const sourceIndex = rows.value.findIndex((row) => row.id === draggedId.value);
    const targetIndex = rows.value.findIndex((row) => row.id === targetRecord.id);

    if (sourceIndex === -1 || targetIndex === -1) {
        draggedId.value = null;
        return;
    }

    const nextRows = [...rows.value];
    const [moved] = nextRows.splice(sourceIndex, 1);
    nextRows.splice(targetIndex, 0, moved);
    rows.value = nextRows;
    draggedId.value = null;
};

const saveOrder = () => {
    if (!canReorder.value || !hasOrderChanges.value) return;

    savingOrder.value = true;
    router.post(
        `${resourceUrl()}/reorder`,
        {
            ids: rows.value.map((row) => row.id),
            start_order: props.records?.from ?? 1,
        },
        {
            preserveScroll: true,
            onFinish: () => (savingOrder.value = false),
        },
    );
};

const resetOrder = () => {
    rows.value = [...(props.records?.data ?? [])];
};
</script>

<template>
    <AdminLayout title="Master Data - Admin">
        <Head :title="`Master Data: ${label}`" />

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <MasterDataSidebar 
                :resources="resources" 
                :currentResource="currentResource" 
            />

            <section class="min-w-0 lg:col-span-9">
                <UiSurface v-if="!currentResource" class="min-h-[520px]" padding="lg">
                    <div class="grid h-full place-items-center py-16 text-center">
                        <div class="max-w-md">
                            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="pi pi-database text-2xl" />
                            </div>
                            <h1 class="mt-5 text-balance text-2xl font-black text-slate-900">Pilih master data</h1>
                            <p class="mt-2 text-pretty text-sm text-slate-500">
                                Kelola data referensi untuk form pembanding dari menu di sebelah kiri.
                            </p>
                        </div>
                    </div>
                </UiSurface>

                <template v-else>
                    <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h1 class="text-balance text-2xl font-black text-slate-900">{{ label }}</h1>
                            <p class="mt-1 text-pretty text-sm text-slate-500">
                                Tambah, edit, urutkan, filter status, dan hapus data referensi.
                            </p>
                        </div>

                        <Button v-if="props.can.create" label="Tambah Data" icon="pi pi-plus" @click="openCreate" />
                    </div>

                    <UiSurface padding="none" class="overflow-hidden">
                        <MasterDataFilter 
                            :filterState="filterState" 
                            :canReorder="canReorder" 
                            :canBulkDelete="props.can.deleteAny"
                            :hasOrderChanges="hasOrderChanges" 
                            :savingOrder="savingOrder" 
                            :deletingBulk="deletingBulk" 
                            :selectedIdsLength="selectedIds.length" 
                            @applyFilters="applyFilters" 
                            @resetFilters="resetFilters" 
                            @saveOrder="saveOrder" 
                            @resetOrder="resetOrder" 
                            @bulkDelete="bulkDelete" 
                        />

                        <MasterDataTable 
                            :rows="rows" 
                            :records="records" 
                            :supportsBadgeColor="supportsBadgeColor" 
                            :canReorder="canReorder" 
                            :canSelect="props.can.deleteAny"
                            :canCreate="props.can.create"
                            :canUpdate="props.can.update"
                            :canToggleStatus="props.can.toggleStatus"
                            :canDelete="props.can.delete"
                            :draggedId="draggedId" 
                            :selectedIds="selectedIds" 
                            :allVisibleSelected="allVisibleSelected" 
                            @toggleAllVisible="toggleAllVisible" 
                            @toggleSelected="toggleSelected" 
                            @toggleStatus="toggleStatus" 
                            @openEdit="openEdit" 
                            @deleteRecord="deleteRecord" 
                            @onDragStart="onDragStart" 
                            @onDrop="onDrop" 
                            @openCreate="openCreate" 
                        />
                    </UiSurface>
                </template>
            </section>
        </div>

        <MasterDataForm 
            v-model:visible="showForm" 
            :form="form" 
            :editingRecord="editingRecord" 
            :label="label" 
            :supportsBadgeColor="supportsBadgeColor" 
            @submit="submit" 
        />
    </AdminLayout>
</template>
