<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import InputText from "primevue/inputtext";
import Checkbox from "primevue/checkbox";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import UiSurface from "../ui/UiSurface.vue";
import UiSectionHeader from "../ui/UiSectionHeader.vue";
import UiField from "../ui/UiField.vue";
import UiEmptyState from "../ui/UiEmptyState.vue";
import { apiRequest } from "../../utils/apiRequest";

const props = defineProps({
    type: { type: String, required: true },
    label: { type: String, required: true },
    icon: { type: String, default: "pi-tag" },
    extra: { type: Array, default: () => [] },
});

const emit = defineEmits(["success", "error"]);

const loadingFetch = ref(false);
const loadingSubmit = ref(false);
const loadingReorder = ref(false);
const deletingId = ref(null);

const items = ref([]);
const originalOrderIds = ref([]);

const query = ref("");
const selectedId = ref(null);

const deleteDialogVisible = ref(false);
const pendingDeleteId = ref(null);
const errorMessage = ref("");

const activeInputId = computed(() => `is_active_${props.type}`);

const badgeOptions = [
    { label: "Gray", value: "gray" },
    { label: "Primary", value: "primary" },
    { label: "Info", value: "info" },
    { label: "Success", value: "success" },
    { label: "Warning", value: "warning" },
    { label: "Danger", value: "danger" },
];

const form = reactive({
    name: "",
    is_active: true,
    badge_color_token: null,
    marker_icon_url: "",
});

const isAnyLoading = computed(
    () => loadingFetch.value || loadingSubmit.value || loadingReorder.value || deletingId.value !== null,
);

const filteredItems = computed(() => {
    const q = query.value.trim().toLowerCase();
    const list = items.value ?? [];
    if (!q) return list;
    return list.filter((item) => `${item.name ?? ""}`.toLowerCase().includes(q) || `${item.slug ?? ""}`.toLowerCase().includes(q));
});

const selectedItem = computed(() => {
    const id = selectedId.value;
    if (!id) return null;
    return (items.value ?? []).find((i) => i.id === id) ?? null;
});

const isEditing = computed(() => Boolean(selectedItem.value));

const hasOrderChanges = computed(() => {
    const currentIds = (items.value ?? []).map((item) => item.id);
    const baselineIds = originalOrderIds.value ?? [];
    if (currentIds.length !== baselineIds.length) return true;
    return currentIds.some((id, index) => id !== baselineIds[index]);
});

const resetForm = () => {
    form.name = "";
    form.is_active = true;
    form.badge_color_token = null;
    form.marker_icon_url = "";
};

const selectNew = () => {
    selectedId.value = null;
    resetForm();
};

const selectItem = (item) => {
    selectedId.value = item?.id ?? null;
    form.name = item?.name ?? "";
    form.is_active = Boolean(item?.is_active ?? true);
    form.badge_color_token = item?.badge_color_token ?? null;
    form.marker_icon_url = item?.marker_icon_url ?? "";
};

const loadItems = async ({ preserveSelection = true } = {}) => {
    loadingFetch.value = true;
    try {
        const payload = await apiRequest(`/home/master-data/dictionaries/${props.type}`);
        items.value = payload ?? [];
        originalOrderIds.value = (payload ?? []).map((item) => item.id);

        if (!preserveSelection) {
            selectNew();
            return;
        }

        const currentSelectedId = selectedId.value;
        if (!currentSelectedId) return;
        const stillExists = (items.value ?? []).some((i) => i.id === currentSelectedId);
        if (!stillExists) selectNew();
        else selectItem((items.value ?? []).find((i) => i.id === currentSelectedId));
    } catch (e) {
        const message = e?.message || "Gagal memuat data";
        errorMessage.value = message;
        emit("error", message);
        items.value = [];
        originalOrderIds.value = [];
    } finally {
        loadingFetch.value = false;
    }
};

const submit = async () => {
    const name = form.name.trim();
    if (!name) return;

    loadingSubmit.value = true;
    try {
        errorMessage.value = "";
        const payload = {
            name,
            is_active: Boolean(form.is_active),
        };

        if (props.extra.includes("badge_color_token")) payload.badge_color_token = form.badge_color_token || null;
        if (props.extra.includes("marker_icon_url")) payload.marker_icon_url = form.marker_icon_url?.trim() || null;

        if (isEditing.value) {
            await apiRequest(`/home/master-data/dictionaries/${props.type}/${selectedId.value}`, {
                method: "PUT",
                body: payload,
            });
            emit("success", "Data diperbarui");
        } else {
            const created = await apiRequest(`/home/master-data/dictionaries/${props.type}`, {
                method: "POST",
                body: payload,
            });
            emit("success", "Data ditambahkan");
            await loadItems({ preserveSelection: false });
            if (created?.id) {
                const match = (items.value ?? []).find((i) => i.id === created.id);
                if (match) selectItem(match);
            }
            return;
        }

        await loadItems({ preserveSelection: true });
    } catch (e) {
        const message = e?.message || "Gagal menyimpan data";
        errorMessage.value = message;
        emit("error", message);
    } finally {
        loadingSubmit.value = false;
    }
};

const openDelete = (id) => {
    if (!id || isAnyLoading.value) return;
    pendingDeleteId.value = id;
    deleteDialogVisible.value = true;
};

const confirmDelete = async () => {
    const id = pendingDeleteId.value;
    if (!id) return;
    deletingId.value = id;
    try {
        errorMessage.value = "";
        await apiRequest(`/home/master-data/dictionaries/${props.type}/${id}`, { method: "DELETE" });
        emit("success", "Data dihapus");
        deleteDialogVisible.value = false;
        pendingDeleteId.value = null;
        if (selectedId.value === id) selectNew();
        await loadItems({ preserveSelection: true });
    } catch (e) {
        const message = e?.message || "Gagal menghapus data";
        errorMessage.value = message;
        emit("error", message);
    } finally {
        deletingId.value = null;
    }
};

const moveItem = (id, direction) => {
    if (!id) return;
    if (query.value.trim()) return; // avoid reordering in filtered state
    const list = items.value ?? [];
    const index = list.findIndex((i) => i.id === id);
    if (index === -1) return;

    const nextIndex = direction === "up" ? index - 1 : index + 1;
    if (nextIndex < 0 || nextIndex >= list.length) return;

    const copy = [...list];
    const tmp = copy[index];
    copy[index] = copy[nextIndex];
    copy[nextIndex] = tmp;
    items.value = copy;
};

const resetOrder = () => {
    const baseline = originalOrderIds.value ?? [];
    const byId = new Map((items.value ?? []).map((i) => [i.id, i]));
    items.value = baseline.map((id) => byId.get(id)).filter(Boolean);
};

const saveOrder = async () => {
    if (!hasOrderChanges.value) return;
    loadingReorder.value = true;
    try {
        errorMessage.value = "";
        await apiRequest(`/home/master-data/dictionaries/${props.type}/reorder`, {
            method: "POST",
            body: { ids: (items.value ?? []).map((i) => i.id) },
        });
        emit("success", "Urutan disimpan");
        await loadItems({ preserveSelection: true });
    } catch (e) {
        const message = e?.message || "Gagal menyimpan urutan";
        errorMessage.value = message;
        emit("error", message);
        await loadItems({ preserveSelection: true });
    } finally {
        loadingReorder.value = false;
    }
};

onMounted(() => {
    loadItems({ preserveSelection: false });
});

watch(
    () => props.type,
    () => {
        query.value = "";
        selectNew();
        loadItems({ preserveSelection: false });
    },
);
</script>

<template>
    <UiSurface padding="none" class="overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <UiSectionHeader :title="label" subtitle="Tambah, edit, nonaktifkan, dan urutkan master data." :icon="`pi ${icon}`">
                <template #actions>
                    <Button
                        label="Tambah"
                        icon="pi pi-plus"
                        size="small"
                        :disabled="isAnyLoading"
                        @click="selectNew"
                    />
                </template>
            </UiSectionHeader>
        </div>

        <div class="grid gap-4 p-4 lg:grid-cols-[360px_1fr]">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <InputText v-model="query" placeholder="Cari nama atau slug..." class="w-full filter-light" />
                    <Button
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        size="small"
                        aria-label="Muat ulang"
                        :loading="loadingFetch"
                        :disabled="isAnyLoading"
                        @click="loadItems({ preserveSelection: true })"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Simpan urutan"
                        icon="pi pi-sort"
                        severity="secondary"
                        outlined
                        size="small"
                        :loading="loadingReorder"
                        :disabled="isAnyLoading || !hasOrderChanges"
                        @click="saveOrder"
                    />
                    <Button
                        label="Reset urutan"
                        icon="pi pi-undo"
                        severity="secondary"
                        outlined
                        size="small"
                        :disabled="isAnyLoading || !hasOrderChanges"
                        @click="resetOrder"
                    />
                    <span v-if="query.trim()" class="text-xs text-slate-500">
                        Urutkan nonaktif saat pencarian.
                    </span>
                </div>

                <UiEmptyState
                    v-if="!loadingFetch && filteredItems.length === 0"
                    title="Tidak ada data"
                    description="Coba ubah kata kunci pencarian atau tambah data baru."
                    icon="pi pi-inbox"
                >
                    <template #actions>
                        <Button label="Tambah data" icon="pi pi-plus" size="small" @click="selectNew" />
                    </template>
                </UiEmptyState>

                <div v-else class="overflow-hidden rounded-[var(--radius-lg)] border border-slate-200 bg-white">
                    <div class="divide-y divide-slate-100">
                        <button
                            v-for="item in filteredItems"
                            :key="item.id"
                            type="button"
                            class="w-full px-3 py-3 text-left"
                            :class="item.id === selectedId ? 'bg-slate-50' : 'hover:bg-slate-50/60'"
                            @click="selectItem(item)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">
                                        {{ item.name }}
                                    </p>
                                    <p class="ui-tabular mt-0.5 truncate text-xs text-slate-500">
                                        {{ item.slug }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    <span
                                        class="ui-tabular rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                        :class="item.is_active ? 'border-slate-200 bg-white text-slate-600' : 'border-slate-200 bg-slate-50 text-slate-500'"
                                    >
                                        {{ item.is_active ? "Aktif" : "Nonaktif" }}
                                    </span>

                                    <button
                                        type="button"
                                        class="ui-hit inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-2 text-slate-700 hover:bg-slate-50"
                                        :disabled="isAnyLoading || query.trim()"
                                        aria-label="Pindahkan ke atas"
                                        @click.stop="moveItem(item.id, 'up')"
                                    >
                                        <i class="pi pi-arrow-up text-[12px]" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="ui-hit inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-2 text-slate-700 hover:bg-slate-50"
                                        :disabled="isAnyLoading || query.trim()"
                                        aria-label="Pindahkan ke bawah"
                                        @click.stop="moveItem(item.id, 'down')"
                                    >
                                        <i class="pi pi-arrow-down text-[12px]" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <UiSurface padding="none" class="overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                    <UiSectionHeader
                        :title="isEditing ? 'Edit data' : 'Tambah data'"
                        :subtitle="isEditing ? `#${selectedId}` : 'Buat entri baru di kamus.'"
                        icon="pi pi-pencil"
                    />
                </div>

                <div class="space-y-4 p-4">
                    <UiField id="md_name" label="Nama" :required="true">
                        <InputText v-model="form.name" id="md_name" class="w-full filter-light" placeholder="Tulis nama..." />
                    </UiField>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.is_active" :input-id="activeInputId" binary />
                        <label :for="activeInputId" class="text-sm font-medium text-slate-700">Aktif</label>
                    </div>

                    <div v-if="props.extra.includes('badge_color_token')" class="grid gap-4 sm:grid-cols-2">
                        <UiField id="md_badge" label="Badge color" help="Hanya untuk Jenis Listing.">
                            <Dropdown
                                v-model="form.badge_color_token"
                                :options="badgeOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Pilih"
                                show-clear
                                class="w-full filter-light"
                                inputId="md_badge"
                            />
                        </UiField>

                        <UiField id="md_marker" label="Marker icon URL" :error="null">
                            <InputText
                                v-model="form.marker_icon_url"
                                id="md_marker"
                                class="w-full filter-light"
                                placeholder="https://..."
                            />
                        </UiField>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                        <Button
                            :label="isEditing ? 'Simpan perubahan' : 'Tambah data'"
                            icon="pi pi-save"
                            :loading="loadingSubmit"
                            :disabled="isAnyLoading || !form.name.trim()"
                            @click="submit"
                        />
                        <Button
                            label="Reset"
                            icon="pi pi-refresh"
                            severity="secondary"
                            outlined
                            :disabled="isAnyLoading"
                            @click="isEditing ? selectItem(selectedItem) : resetForm()"
                        />

                        <Button
                            v-if="isEditing"
                            label="Hapus"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            class="sm:ml-auto"
                            :disabled="isAnyLoading"
                            @click="openDelete(selectedId)"
                        />
                    </div>

                    <p v-if="errorMessage" class="text-pretty text-xs font-medium text-red-600">
                        {{ errorMessage }}
                    </p>
                </div>
            </UiSurface>
        </div>
    </UiSurface>

    <Dialog v-model:visible="deleteDialogVisible" :modal="true" :closable="false" :draggable="false" style="width: min(520px, 100%)">
        <template #header>
            <div class="space-y-1">
                <h3 class="text-balance text-base font-semibold text-slate-900">Hapus data?</h3>
                <p class="text-pretty text-xs text-slate-500">Aksi ini akan menghapus entri dari kamus.</p>
            </div>
        </template>

        <p class="text-pretty text-sm text-slate-700">
            Data yang dihapus mungkin memengaruhi form pembanding jika masih digunakan.
        </p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Batal" severity="secondary" outlined :disabled="deletingId !== null" @click="deleteDialogVisible = false" />
                <Button
                    label="Hapus"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="deletingId !== null"
                    @click="confirmDelete"
                />
            </div>
        </template>
    </Dialog>
</template>
