<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiField from "../../../components/ui/UiField.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    currentResource: { type: String, default: null },
    label: { type: String, default: "Master Data" },
    records: { type: Object, default: () => ({ data: [], links: [] }) },
    resources: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    supportsBadgeColor: { type: Boolean, default: false },
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
const selectedIds = ref([]);
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

const statusOptions = [
    { label: "Semua status", value: "all" },
    { label: "Aktif", value: "active" },
    { label: "Nonaktif", value: "inactive" },
];

const sortOptions = [
    { label: "Urutan", value: "sort_order" },
    { label: "Nama", value: "name" },
    { label: "Slug", value: "slug" },
    { label: "Status", value: "is_active" },
];

const perPageOptions = [10, 20, 50, 100];

const slugPreview = computed(() => {
    const value = form.name
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-|-$/g, "");

    return value || "slug-otomatis";
});

const allVisibleSelected = computed(() => {
    const ids = rows.value.map((row) => row.id);
    return ids.length > 0 && ids.every((id) => selectedIds.value.includes(id));
});

const hasOrderChanges = computed(() => {
    const original = (props.records?.data ?? []).map((row) => row.id).join(",");
    const current = rows.value.map((row) => row.id).join(",");

    return original !== current;
});

const canReorder = computed(() => {
    return props.currentResource && filterState.sort_by === "sort_order" && filterState.sort_dir === "asc";
});

watch(
    () => props.records?.data,
    (data) => {
        rows.value = [...(data ?? [])];
        selectedIds.value = selectedIds.value.filter((id) => rows.value.some((row) => row.id === id));
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

let searchTimeout = null;
watch(
    () => filterState.search,
    () => {
        if (!props.currentResource) return;
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    },
);

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
    editingRecord.value = record;
    form.clearErrors();
    form.name = record.name ?? "";
    form.sort_order = record.sort_order ?? 0;
    form.is_active = Boolean(record.is_active);
    form.badge_color = record.badge_color || "#64748b";
    showForm.value = true;
};

const submit = () => {
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
    router.patch(`${resourceUrl()}/${record.id}/toggle-status`, {}, { preserveScroll: true });
};

const toggleAllVisible = () => {
    const ids = rows.value.map((row) => row.id);

    selectedIds.value = allVisibleSelected.value
        ? selectedIds.value.filter((id) => !ids.includes(id))
        : [...new Set([...selectedIds.value, ...ids])];
};

const toggleSelected = (id) => {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((selectedId) => selectedId !== id)
        : [...selectedIds.value, id];
};

const bulkDelete = () => {
    if (selectedIds.value.length === 0) return;

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
                    onSuccess: () => (selectedIds.value = []),
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
    if (!hasOrderChanges.value) return;

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

const formatStatusSeverity = (active) => active ? "success" : "danger";
</script>

<template>
    <AdminLayout title="Master Data - Admin">
        <Head :title="`Master Data: ${label}`" />

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-balance text-sm font-bold text-slate-900">Menu Master Data</p>
                        <p class="mt-1 text-pretty text-xs text-slate-500">Pilih kategori referensi.</p>
                    </div>

                    <nav class="p-2">
                        <Link
                            v-for="res in resources"
                            :key="res.slug"
                            :href="resourceUrl(res.slug)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold"
                            :class="currentResource === res.slug ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                        >
                            <i :class="res.icon" class="shrink-0 text-xs" />
                            <span class="truncate">{{ res.label }}</span>
                        </Link>
                    </nav>
                </UiSurface>
            </aside>

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

                        <Button label="Tambah Data" icon="pi pi-plus" @click="openCreate" />
                    </div>

                    <UiSurface padding="none" class="overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                                <span class="relative xl:col-span-2">
                                    <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                                    <InputText
                                        v-model="filterState.search"
                                        class="w-full pl-9"
                                        placeholder="Cari nama atau slug"
                                    />
                                </span>

                                <Dropdown
                                    v-model="filterState.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full"
                                    @change="applyFilters"
                                />

                                <Dropdown
                                    v-model="filterState.sort_by"
                                    :options="sortOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full"
                                    @change="applyFilters"
                                />

                                <div class="grid grid-cols-[1fr_auto] gap-2">
                                    <Dropdown
                                        v-model="filterState.per_page"
                                        :options="perPageOptions"
                                        class="w-full"
                                        @change="applyFilters"
                                    />
                                    <Button
                                        :icon="filterState.sort_dir === 'asc' ? 'pi pi-sort-amount-down' : 'pi pi-sort-amount-up'"
                                        severity="secondary"
                                        outlined
                                        aria-label="Ubah arah sort"
                                        @click="filterState.sort_dir = filterState.sort_dir === 'asc' ? 'desc' : 'asc'; applyFilters()"
                                    />
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <Button
                                    label="Reset Filter"
                                    icon="pi pi-filter-slash"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    @click="resetFilters"
                                />
                                <Button
                                    label="Simpan Urutan"
                                    icon="pi pi-save"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    :disabled="!canReorder || !hasOrderChanges"
                                    :loading="savingOrder"
                                    @click="saveOrder"
                                />
                                <Button
                                    label="Reset Urutan"
                                    icon="pi pi-undo"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    :disabled="!hasOrderChanges"
                                    @click="resetOrder"
                                />
                                <Button
                                    label="Hapus Terpilih"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    outlined
                                    size="small"
                                    :disabled="selectedIds.length === 0"
                                    :loading="deletingBulk"
                                    @click="bulkDelete"
                                />
                                <span v-if="!canReorder" class="text-pretty text-xs font-medium text-slate-500">
                                    Drag urutan aktif saat sort = Urutan dan arah = asc.
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[880px] text-left text-sm">
                                <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                                    <tr>
                                        <th class="w-12 px-5 py-4">
                                            <button
                                                type="button"
                                                class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                                                aria-label="Pilih semua data terlihat"
                                                @click="toggleAllVisible"
                                            >
                                                <i v-if="allVisibleSelected" class="pi pi-check text-[10px] text-slate-700" />
                                            </button>
                                        </th>
                                        <th class="w-16 px-5 py-4">Urut</th>
                                        <th class="px-5 py-4">Nama / Slug</th>
                                        <th class="px-5 py-4">Status</th>
                                        <th v-if="supportsBadgeColor" class="px-5 py-4">Badge</th>
                                        <th class="px-5 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="record in rows"
                                        :key="record.id"
                                        class="group hover:bg-slate-50"
                                        :class="draggedId === record.id ? 'opacity-50' : ''"
                                        :draggable="canReorder"
                                        @dragstart="onDragStart(record)"
                                        @dragover.prevent
                                        @drop="onDrop(record)"
                                    >
                                        <td class="px-5 py-4">
                                            <button
                                                type="button"
                                                class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                                                :aria-label="`Pilih ${record.name}`"
                                                @click="toggleSelected(record.id)"
                                            >
                                                <i v-if="selectedIds.includes(record.id)" class="pi pi-check text-[10px] text-slate-700" />
                                            </button>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                <i
                                                    class="pi pi-bars text-xs"
                                                    :class="canReorder ? 'cursor-grab text-slate-400 group-hover:text-slate-700' : 'text-slate-200'"
                                                    aria-hidden="true"
                                                />
                                                <span class="ui-tabular rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs font-bold text-slate-600">
                                                    {{ record.sort_order }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="max-w-md truncate font-bold text-slate-900">{{ record.name }}</p>
                                            <p class="ui-tabular mt-0.5 max-w-md truncate text-xs text-slate-500">{{ record.slug }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <button type="button" class="text-left" @click="toggleStatus(record)">
                                                <Tag
                                                    :value="record.is_active ? 'Aktif' : 'Nonaktif'"
                                                    :severity="formatStatusSeverity(record.is_active)"
                                                />
                                            </button>
                                        </td>
                                        <td v-if="supportsBadgeColor" class="px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="size-4 rounded-full border border-slate-200" :style="{ backgroundColor: record.badge_color || '#64748b' }" />
                                                <span class="ui-tabular text-xs font-semibold text-slate-500">{{ record.badge_color || '#64748b' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-1">
                                                <Button icon="pi pi-pencil" text rounded severity="secondary" aria-label="Edit" @click="openEdit(record)" />
                                                <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Hapus" @click="deleteRecord(record)" />
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="rows.length === 0">
                                        <td :colspan="supportsBadgeColor ? 6 : 5" class="px-5 py-8">
                                            <UiEmptyState
                                                title="Data tidak ditemukan"
                                                description="Ubah filter pencarian atau tambah data baru."
                                                icon="pi pi-database"
                                            >
                                                <template #actions>
                                                    <Button label="Tambah Data" icon="pi pi-plus" size="small" @click="openCreate" />
                                                </template>
                                            </UiEmptyState>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="records.links?.length > 3"
                            class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="ui-tabular text-xs font-semibold text-slate-500">
                                Halaman {{ records.current_page }} dari {{ records.last_page }}
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(link, i) in records.links" :key="i">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                                        :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                    />
                                    <span
                                        v-else
                                        v-html="link.label"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-300"
                                    />
                                </template>
                            </div>
                        </div>
                    </UiSurface>
                </template>
            </section>
        </div>

        <Dialog
            v-model:visible="showForm"
            modal
            :draggable="false"
            :header="editingRecord ? `Edit ${label}` : `Tambah ${label}`"
            style="width: min(520px, 100%)"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <UiField id="master_name" label="Nama Data" required :error="form.errors.name">
                    <InputText id="master_name" v-model="form.name" class="w-full" placeholder="Contoh: Sertifikat Hak Milik" />
                </UiField>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs font-semibold text-slate-500">Slug otomatis</p>
                    <p class="ui-tabular mt-1 text-sm font-bold text-slate-900">{{ slugPreview }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField id="master_order" label="Urutan" :error="form.errors.sort_order" help="Kosongkan saat create untuk memakai urutan terakhir.">
                        <InputNumber
                            v-model="form.sort_order"
                            input-id="master_order"
                            class="w-full"
                            input-class="w-full"
                            :min="0"
                            placeholder="Otomatis"
                        />
                    </UiField>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-slate-600">Status</p>
                        <div class="flex items-center gap-2 pt-2">
                            <Checkbox v-model="form.is_active" input-id="master_active" binary />
                            <label for="master_active" class="text-sm font-medium text-slate-700">Aktif</label>
                        </div>
                    </div>
                </div>

                <UiField v-if="supportsBadgeColor" id="master_badge" label="Warna Badge" :error="form.errors.badge_color">
                    <div class="flex items-center gap-3">
                        <input
                            id="master_badge"
                            v-model="form.badge_color"
                            type="color"
                            class="size-10 cursor-pointer rounded-lg border border-slate-200 bg-white"
                        />
                        <InputText v-model="form.badge_color" class="w-full font-mono text-xs" placeholder="#64748b" />
                    </div>
                </UiField>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <Button label="Batal" severity="secondary" outlined :disabled="form.processing" @click="showForm = false" />
                    <Button
                        :label="editingRecord ? 'Simpan Perubahan' : 'Tambah Data'"
                        icon="pi pi-save"
                        type="submit"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
