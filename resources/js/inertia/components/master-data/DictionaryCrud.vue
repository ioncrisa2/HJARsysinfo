<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import InputText from "primevue/inputtext";
import Checkbox from "primevue/checkbox";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Tag from "primevue/tag";
import { apiRequest } from "../../utils/apiRequest";

const props = defineProps({
    type: { type: String, required: true },
    label: { type: String, required: true },
    icon: { type: String, default: "pi-tag" },
    extra: { type: Array, default: () => [] }, // e.g. ['badge_color_token','marker_icon_url']
});

const emit = defineEmits(["success", "error"]);

// ─── Granular loading states ──────────────────────────────────────────────────
const loadingFetch    = ref(false);
const loadingSubmit   = ref(false);
const loadingReorder  = ref(false);
const deletingId      = ref(null); // tracks which item is being deleted

const isAnyLoading = computed(() =>
    loadingFetch.value || loadingSubmit.value || loadingReorder.value || deletingId.value !== null
);
// ─────────────────────────────────────────────────────────────────────────────

const items              = ref([]);
const editingId          = ref(null);
const activeInputId      = computed(() => `is_active_${props.type}`);
const originalOrderIds   = ref([]);
const dragSourceIndex    = ref(null);
const dragOverIndex      = ref(null);

// Snapshot taken just before a drag-save attempt — used for auto-rollback
let reorderSnapshot = null;

const form = reactive({
    name: "",
    is_active: true,
    badge_color_token: null,
    marker_icon_url: "",
});

const badgeOptions = [
    { label: "Gray",    value: "gray"    },
    { label: "Primary", value: "primary" },
    { label: "Info",    value: "info"    },
    { label: "Success", value: "success" },
    { label: "Warning", value: "warning" },
    { label: "Danger",  value: "danger"  },
];

const resetForm = () => {
    editingId.value        = null;
    form.name              = "";
    form.is_active         = true;
    form.badge_color_token = null;
    form.marker_icon_url   = "";
};

const hasOrderChanges = computed(() => {
    const currentIds = items.value.map((item) => item.id);
    if (currentIds.length !== originalOrderIds.value.length) return true;
    return currentIds.some((id, index) => id !== originalOrderIds.value[index]);
});

// ─── Data loading ─────────────────────────────────────────────────────────────
const loadItems = async () => {
    loadingFetch.value = true;
    try {
        const payload = await apiRequest(`/home/master-data/dictionaries/${props.type}`);
        items.value          = payload;
        originalOrderIds.value = payload.map((item) => item.id);
    } catch (e) {
        emit("error", e.message || "Gagal memuat data");
    } finally {
        loadingFetch.value = false;
    }
};

// ─── Submit (add / edit) ──────────────────────────────────────────────────────
const submit = async () => {
    loadingSubmit.value = true;
    try {
        const isEditing = Boolean(editingId.value);
        const payload   = { name: form.name, is_active: form.is_active };

        if (props.extra.includes("badge_color_token")) payload.badge_color_token = form.badge_color_token;
        if (props.extra.includes("marker_icon_url"))   payload.marker_icon_url   = form.marker_icon_url || null;

        await apiRequest(
            `/home/master-data/dictionaries/${props.type}${editingId.value ? "/" + editingId.value : ""}`,
            { method: editingId.value ? "PUT" : "POST", body: payload }
        );

        await loadItems();
        resetForm();
        emit("success", isEditing ? "Berhasil diperbarui" : "Berhasil ditambahkan");
    } catch (e) {
        emit("error", e.message || "Gagal menyimpan");
    } finally {
        loadingSubmit.value = false;
    }
};

// ─── Edit / Delete ────────────────────────────────────────────────────────────
const editItem = (item) => {
    editingId.value        = item.id;
    form.name              = item.name;
    form.is_active         = Boolean(item.is_active);
    form.badge_color_token = item.badge_color_token ?? null;
    form.marker_icon_url   = item.marker_icon_url   ?? "";
};

const deleteItem = async (id) => {
    if (!confirm("Hapus data ini?")) return;
    deletingId.value = id;
    try {
        await apiRequest(`/home/master-data/dictionaries/${props.type}/${id}`, { method: "DELETE" });
        await loadItems();
        emit("success", "Berhasil dihapus");
    } catch (e) {
        emit("error", e.message || "Gagal menghapus");
    } finally {
        deletingId.value = null;
    }
};

// ─── Drag & drop reorder ──────────────────────────────────────────────────────
const moveItem = (fromIndex, toIndex) => {
    if (fromIndex === toIndex) return;
    if (fromIndex < 0 || toIndex < 0) return;
    if (fromIndex >= items.value.length || toIndex >= items.value.length) return;

    const reordered    = [...items.value];
    const [moved]      = reordered.splice(fromIndex, 1);
    reordered.splice(toIndex, 0, moved);
    items.value        = reordered;
};

const onDragStart = (event, index) => {
    dragSourceIndex.value = index;
    event.dataTransfer?.setData("text/plain", String(items.value[index]?.id ?? ""));
    if (event.dataTransfer) event.dataTransfer.effectAllowed = "move";
};

const onDragOver = (event, index) => {
    event.preventDefault();
    dragOverIndex.value = index;
    if (event.dataTransfer) event.dataTransfer.dropEffect = "move";
};

const onDrop = (event, index) => {
    event.preventDefault();
    const fromIndex = dragSourceIndex.value;
    if (fromIndex === null) return;
    moveItem(fromIndex, index);
    dragSourceIndex.value = null;
    dragOverIndex.value   = null;
};

const onDragEnd = () => {
    dragSourceIndex.value = null;
    dragOverIndex.value   = null;
};

const resetOrder = () => {
    if (!hasOrderChanges.value) return;
    const mapById  = new Map(items.value.map((item) => [item.id, item]));
    items.value    = originalOrderIds.value
        .map((id) => mapById.get(id))
        .filter(Boolean);
};

const saveOrder = async () => {
    if (!hasOrderChanges.value) return;

    // Take a snapshot before touching the server — enables auto-rollback
    reorderSnapshot        = [...items.value];
    loadingReorder.value   = true;

    try {
        const ids = items.value.map((item) => item.id);
        await apiRequest(`/home/master-data/dictionaries/${props.type}/reorder`, {
            method: "POST",
            body: { ids },
        });

        emit("success", "Urutan berhasil disimpan");
        await loadItems(); // refresh so originalOrderIds is updated
    } catch (e) {
        // Auto-rollback: restore the list to pre-save state
        if (reorderSnapshot) {
            items.value = reorderSnapshot;
        }
        emit("error", e.message || "Gagal menyimpan urutan");
    } finally {
        reorderSnapshot      = null;
        loadingReorder.value = false;
    }
};

onMounted(loadItems);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i :class="['pi', icon, 'text-amber-500', 'text-xs']" />
                {{ label }}
            </div>
            <div class="flex items-center gap-2">
                <Button
                    label="Simpan Urutan"
                    icon="pi pi-check"
                    size="small"
                    :disabled="!hasOrderChanges || isAnyLoading"
                    :loading="loadingReorder"
                    @click="saveOrder"
                />
                <Button
                    label="Batalkan Urutan"
                    icon="pi pi-undo"
                    size="small"
                    severity="secondary"
                    text
                    :disabled="!hasOrderChanges || isAnyLoading"
                    @click="resetOrder"
                />
                <Tag :value="`${items.length} data`" severity="secondary" />
            </div>
        </div>

        <div class="grid gap-4 p-4 md:grid-cols-[320px_1fr]">
            <!-- ── Form panel ── -->
            <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ editingId ? "Ubah" : "Tambah" }} {{ label }}
                </p>

                <div class="space-y-2">
                    <label class="text-xs text-slate-500">Nama</label>
                    <InputText v-model="form.name" class="w-full" />
                </div>

                <div class="flex items-end gap-2 pt-1">
                    <Checkbox v-model="form.is_active" :input-id="activeInputId" binary />
                    <label :for="activeInputId" class="text-xs text-slate-600">Aktif</label>
                </div>

                <div v-if="props.extra.includes('badge_color_token')" class="space-y-1">
                    <label class="text-xs text-slate-500">Badge Color</label>
                    <Dropdown
                        v-model="form.badge_color_token"
                        :options="badgeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Auto"
                        class="w-full"
                        show-clear
                    />
                </div>

                <div v-if="props.extra.includes('marker_icon_url')" class="space-y-1">
                    <label class="text-xs text-slate-500">Marker Icon URL</label>
                    <InputText v-model="form.marker_icon_url" class="w-full" placeholder="https://..." />
                </div>

                <div class="flex gap-2">
                    <Button
                        :label="editingId ? 'Update' : 'Simpan'"
                        icon="pi pi-save"
                        :loading="loadingSubmit"
                        :disabled="isAnyLoading"
                        @click="submit"
                    />
                    <Button
                        label="Reset"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        :disabled="loadingSubmit"
                        @click="resetForm"
                    />
                </div>
            </div>

            <!-- ── Table panel ── -->
            <div class="overflow-hidden rounded-xl border border-slate-100">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-2 text-center">Geser</th>
                            <th class="px-3 py-2 text-left">Nama</th>
                            <th class="px-3 py-2 text-left">Slug</th>
                            <th class="px-3 py-2 text-center">Urutan</th>
                            <th class="px-3 py-2 text-center">Aktif</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            draggable="true"
                            class="hover:bg-slate-50"
                            :class="{
                                'bg-amber-50/70': dragOverIndex === index,
                                'opacity-70':     dragSourceIndex === index,
                            }"
                            @dragstart="onDragStart($event, index)"
                            @dragover="onDragOver($event, index)"
                            @drop="onDrop($event, index)"
                            @dragend="onDragEnd"
                        >
                            <td class="px-3 py-2 text-center text-slate-400">
                                <i class="pi pi-bars text-xs" />
                            </td>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ item.name }}</td>
                            <td class="px-3 py-2 text-xs text-slate-500">{{ item.slug }}</td>
                            <td class="px-3 py-2 text-center text-xs">{{ index + 1 }}</td>
                            <td class="px-3 py-2 text-center">
                                <Tag
                                    :value="item.is_active ? 'Aktif' : 'Nonaktif'"
                                    :severity="item.is_active ? 'success' : 'secondary'"
                                />
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <Button
                                    icon="pi pi-pencil"
                                    size="small"
                                    text
                                    :disabled="isAnyLoading"
                                    @click="editItem(item)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    size="small"
                                    text
                                    severity="danger"
                                    :loading="deletingId === item.id"
                                    :disabled="isAnyLoading"
                                    @click="deleteItem(item.id)"
                                />
                            </td>
                        </tr>
                        <tr v-if="!items.length && !loadingFetch">
                            <td colspan="6" class="px-3 py-4 text-center text-xs text-slate-400">
                                Tidak ada data
                            </td>
                        </tr>
                        <tr v-if="loadingFetch">
                            <td colspan="6" class="px-3 py-4 text-center text-xs text-slate-400">
                                <i class="pi pi-spin pi-spinner mr-1" /> Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
