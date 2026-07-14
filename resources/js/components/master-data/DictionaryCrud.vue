<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import UiEmptyState from "../ui/UiEmptyState.vue";
import UiField from "../ui/UiField.vue";
import UiSurface from "../ui/UiSurface.vue";
import { apiRequest } from "../../utils/apiRequest";

const props = defineProps({
    category: { type: Object, required: true },
    items: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast = useToast();
const query = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "all");
const dialogVisible = ref(false);
const editingItem = ref(null);
const submitting = ref(false);
const statusUpdatingId = ref(null);
const deletingId = ref(null);
const reorderMode = ref(false);
const savingOrder = ref(false);
const orderedItems = ref([...props.items]);
const formSnapshot = ref("");
const lastDialogTrigger = ref(null);
let removeBeforeListener = null;

const form = reactive({
    name: "",
    badge_color: "",
    marker_icon_url: "",
});

const errors = reactive({
    name: "",
    badge_color: "",
    marker_icon_url: "",
    general: "",
});

const statusOptions = [
    { label: "Semua status", value: "all" },
    { label: "Aktif", value: "active" },
    { label: "Nonaktif", value: "inactive" },
];

const endpoint = `/app/master-data/dictionaries/${props.category.type}`;
const supportsBadgeColor = computed(() => props.category.extra?.includes("badge_color"));
const supportsMarkerIcon = computed(() => props.category.extra?.includes("marker_icon_url"));
const isEditing = computed(() => Boolean(editingItem.value));
const isBusy = computed(() => submitting.value || statusUpdatingId.value !== null || deletingId.value !== null || savingOrder.value);

const filteredItems = computed(() => {
    const keyword = query.value.trim().toLowerCase();

    return props.items.filter((item) => {
        const matchesKeyword = keyword === "" || `${item.name ?? ""}`.toLowerCase().includes(keyword);
        const matchesStatus = status.value === "all"
            || (status.value === "active" && item.is_active)
            || (status.value === "inactive" && !item.is_active);

        return matchesKeyword && matchesStatus;
    });
});

const visibleItems = computed(() => reorderMode.value ? orderedItems.value : filteredItems.value);
const originalOrderIds = computed(() => props.items.map((item) => item.id));
const currentOrderIds = computed(() => orderedItems.value.map((item) => item.id));
const hasOrderChanges = computed(() => currentOrderIds.value.some((id, index) => id !== originalOrderIds.value[index]));
const serializedForm = () => JSON.stringify({
    name: form.name.trim(),
    badge_color: supportsBadgeColor.value ? form.badge_color : "",
    marker_icon_url: supportsMarkerIcon.value ? form.marker_icon_url.trim() : "",
});
const isDirty = computed(() => dialogVisible.value && serializedForm() !== formSnapshot.value);

watch(
    () => props.items,
    (items) => {
        if (!reorderMode.value) orderedItems.value = [...items];
    },
    { deep: true },
);

const clearErrors = () => {
    errors.name = "";
    errors.badge_color = "";
    errors.marker_icon_url = "";
    errors.general = "";
};

const fillForm = (item = null) => {
    form.name = item?.name ?? "";
    form.badge_color = item?.badge_color ?? "";
    form.marker_icon_url = item?.marker_icon_url ?? "";
    clearErrors();
    formSnapshot.value = serializedForm();
};

const openCreate = (event) => {
    if (!props.can.create || isBusy.value) return;
    lastDialogTrigger.value = event?.currentTarget ?? document.activeElement;
    editingItem.value = null;
    fillForm();
    dialogVisible.value = true;
};

const openEdit = (item, event) => {
    if (!props.can.update || isBusy.value) return;
    lastDialogTrigger.value = event?.currentTarget ?? document.activeElement;
    editingItem.value = item;
    fillForm(item);
    dialogVisible.value = true;
};

const closeDialog = () => {
    dialogVisible.value = false;
    editingItem.value = null;
    clearErrors();
    nextTick(() => lastDialogTrigger.value?.focus?.());
};

const requestClose = (visible) => {
    if (visible) {
        dialogVisible.value = true;
        return;
    }

    if (!isDirty.value) {
        closeDialog();
        return;
    }

    confirm.require({
        header: "Buang perubahan?",
        message: "Perubahan yang belum disimpan akan hilang.",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Buang Perubahan",
        rejectLabel: "Kembali Mengedit",
        acceptClass: "p-button-danger",
        accept: closeDialog,
    });
};

const fieldError = (error, field) => {
    const value = error?.payload?.errors?.[field];
    return Array.isArray(value) ? String(value[0] ?? "") : String(value ?? "");
};

const refreshItems = () => router.reload({ only: ["items"], preserveScroll: true });

const submit = async () => {
    clearErrors();
    if (!form.name.trim()) {
        errors.name = `Nama ${props.category.label} wajib diisi.`;
        return;
    }

    submitting.value = true;
    const payload = { name: form.name.trim() };
    if (supportsBadgeColor.value) payload.badge_color = form.badge_color || null;
    if (supportsMarkerIcon.value) payload.marker_icon_url = form.marker_icon_url.trim() || null;

    try {
        if (isEditing.value) {
            await apiRequest(`${endpoint}/${editingItem.value.id}`, { method: "PUT", body: payload });
        } else {
            await apiRequest(endpoint, { method: "POST", body: payload });
        }

        toast.add({
            severity: "success",
            summary: isEditing.value ? `${props.category.label} diperbarui` : `${props.category.label} ditambahkan`,
            life: 4000,
        });
        closeDialog();
        refreshItems();
    } catch (error) {
        errors.name = fieldError(error, "name") || fieldError(error, "slug");
        errors.badge_color = fieldError(error, "badge_color");
        errors.marker_icon_url = fieldError(error, "marker_icon_url");
        errors.general = errors.name || errors.badge_color || errors.marker_icon_url ? "" : error.message;
    } finally {
        submitting.value = false;
    }
};

const toggleStatus = (item) => {
    if (!props.can.update_status || isBusy.value) return;
    const willActivate = !item.is_active;

    confirm.require({
        header: `${willActivate ? "Aktifkan" : "Nonaktifkan"} ${props.category.label}?`,
        message: willActivate
            ? `“${item.name}” akan tersedia kembali untuk input Data Pembanding baru.`
            : `“${item.name}” tidak lagi tersedia untuk input baru. ${item.pembandings_count} Data Pembanding lama tetap mempertahankan nilainya.`,
        icon: willActivate ? "pi pi-check-circle" : "pi pi-info-circle",
        acceptLabel: willActivate ? "Aktifkan" : "Nonaktifkan",
        rejectLabel: "Batal",
        accept: async () => {
            statusUpdatingId.value = item.id;
            try {
                await apiRequest(`${endpoint}/${item.id}/status`, {
                    method: "PATCH",
                    body: { is_active: willActivate },
                });
                toast.add({ severity: "success", summary: `${item.name} ${willActivate ? "diaktifkan" : "dinonaktifkan"}`, life: 4000 });
                refreshItems();
            } catch (error) {
                toast.add({ severity: "error", summary: "Status gagal diperbarui", detail: error.message });
            } finally {
                statusUpdatingId.value = null;
            }
        },
    });
};

const deleteItem = (item) => {
    if (!props.can.delete || item.pembandings_count > 0 || isBusy.value) return;

    confirm.require({
        header: `Hapus ${props.category.label}?`,
        message: `“${item.name}” belum digunakan dan akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Hapus Permanen",
        rejectLabel: "Batal",
        acceptClass: "p-button-danger",
        accept: async () => {
            deletingId.value = item.id;
            try {
                await apiRequest(`${endpoint}/${item.id}`, { method: "DELETE" });
                toast.add({ severity: "success", summary: `${item.name} dihapus`, life: 4000 });
                refreshItems();
            } catch (error) {
                toast.add({ severity: "error", summary: "Data tidak dapat dihapus", detail: error.message });
            } finally {
                deletingId.value = null;
            }
        },
    });
};

const startReorder = () => {
    if (!props.can.reorder || isBusy.value) return;
    query.value = "";
    status.value = "all";
    orderedItems.value = [...props.items];
    reorderMode.value = true;
};

const moveItem = (index, direction) => {
    const nextIndex = direction === "up" ? index - 1 : index + 1;
    if (nextIndex < 0 || nextIndex >= orderedItems.value.length) return;
    const copy = [...orderedItems.value];
    [copy[index], copy[nextIndex]] = [copy[nextIndex], copy[index]];
    orderedItems.value = copy;
};

const cancelReorder = () => {
    orderedItems.value = [...props.items];
    reorderMode.value = false;
};

const saveOrder = async () => {
    if (!hasOrderChanges.value) {
        cancelReorder();
        return;
    }

    savingOrder.value = true;
    try {
        await apiRequest(`${endpoint}/reorder`, {
            method: "POST",
            body: { ids: currentOrderIds.value },
        });
        toast.add({ severity: "success", summary: "Urutan berhasil disimpan", life: 4000 });
        reorderMode.value = false;
        refreshItems();
    } catch (error) {
        toast.add({ severity: "error", summary: "Urutan gagal disimpan", detail: error.message });
    } finally {
        savingOrder.value = false;
    }
};

onMounted(() => {
    removeBeforeListener = router.on("before", (event) => {
        if (isDirty.value && !window.confirm("Perubahan belum disimpan. Tinggalkan halaman ini?")) {
            event.preventDefault();
        }
    });
});

onUnmounted(() => removeBeforeListener?.());
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="!can.create && !can.update && !can.update_status && !can.delete && !can.reorder"
            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
            role="status"
        >
            Anda memiliki akses lihat saja. Perubahan Master Data tidak tersedia untuk akun ini.
        </div>

        <UiSurface padding="none" class="overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(220px,1fr)_180px]">
                        <span class="relative">
                            <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true" />
                            <InputText
                                v-model="query"
                                class="w-full pl-9"
                                :placeholder="`Cari ${category.label.toLowerCase()}`"
                                :disabled="reorderMode"
                                aria-label="Cari berdasarkan nama"
                            />
                        </span>
                        <Select
                            v-model="status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :disabled="reorderMode"
                            class="w-full"
                            aria-label="Filter status"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template v-if="reorderMode">
                            <Button label="Batal" severity="secondary" outlined :disabled="savingOrder" @click="cancelReorder" />
                            <Button label="Simpan Urutan" icon="pi pi-save" :loading="savingOrder" :disabled="!hasOrderChanges" @click="saveOrder" />
                        </template>
                        <template v-else>
                            <Button v-if="can.reorder" label="Atur Urutan" icon="pi pi-sort" severity="secondary" outlined @click="startReorder" />
                            <Button v-if="can.create" :label="`Tambah ${category.label}`" icon="pi pi-plus" @click="openCreate" />
                        </template>
                    </div>
                </div>
                <p v-if="reorderMode" class="mt-3 text-xs font-medium text-slate-600" role="status">
                    Mode Atur Urutan aktif. Gunakan tombol naik dan turun, lalu simpan perubahan.
                </p>
            </div>

            <UiEmptyState
                v-if="visibleItems.length === 0"
                class="m-6"
                title="Data tidak ditemukan"
                description="Ubah pencarian atau filter status, atau tambahkan data baru jika Anda memiliki akses."
                icon="pi pi-database"
            >
                <template #actions>
                    <Button v-if="can.create && !reorderMode" :label="`Tambah ${category.label}`" icon="pi pi-plus" size="small" @click="openCreate" />
                </template>
            </UiEmptyState>

            <template v-else>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                            <tr>
                                <th v-if="reorderMode" class="w-32 px-5 py-4">Urutan</th>
                                <th class="px-5 py-4">Nama</th>
                                <th class="w-32 px-5 py-4">Status</th>
                                <th class="w-40 px-5 py-4">Digunakan</th>
                                <th v-if="!reorderMode && (can.update || can.update_status || can.delete)" class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in visibleItems" :key="item.id" class="hover:bg-slate-50">
                                <td v-if="reorderMode" class="px-5 py-3">
                                    <div class="flex items-center gap-1">
                                        <Button icon="pi pi-arrow-up" text rounded severity="secondary" :disabled="index === 0 || isBusy" :aria-label="`Naikkan ${item.name}`" @click="moveItem(index, 'up')" />
                                        <Button icon="pi pi-arrow-down" text rounded severity="secondary" :disabled="index === visibleItems.length - 1 || isBusy" :aria-label="`Turunkan ${item.name}`" @click="moveItem(index, 'down')" />
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ item.name }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <Tag :value="item.is_active ? 'Aktif' : 'Nonaktif'" :severity="item.is_active ? 'success' : 'secondary'" />
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs text-slate-500">
                                        <span class="ui-tabular font-semibold text-slate-700">{{ item.pembandings_count }}</span>
                                        data
                                    </span>
                                </td>
                                <td v-if="!reorderMode && (can.update || can.update_status || can.delete)" class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button v-if="can.update" label="Edit" icon="pi pi-pencil" text severity="secondary" :disabled="isBusy" @click="openEdit(item, $event)" />
                                        <Button
                                            v-if="can.update_status"
                                            :label="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            :icon="item.is_active ? 'pi pi-ban' : 'pi pi-check'"
                                            text
                                            severity="secondary"
                                            :loading="statusUpdatingId === item.id"
                                            :disabled="isBusy && statusUpdatingId !== item.id"
                                            @click="toggleStatus(item)"
                                        />
                                        <Button
                                            v-if="can.delete"
                                            icon="pi pi-trash"
                                            text
                                            rounded
                                            severity="danger"
                                            :disabled="item.pembandings_count > 0 || isBusy"
                                            :loading="deletingId === item.id"
                                            :title="item.pembandings_count > 0 ? 'Data yang sudah digunakan harus dinonaktifkan, bukan dihapus.' : 'Hapus permanen'"
                                            :aria-label="item.pembandings_count > 0 ? `${item.name} tidak dapat dihapus karena sudah digunakan` : `Hapus ${item.name}`"
                                            @click="deleteItem(item)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 md:hidden">
                    <article v-for="(item, index) in visibleItems" :key="item.id" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-900">{{ item.name }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Digunakan pada {{ item.pembandings_count }} Data Pembanding</p>
                            </div>
                            <Tag :value="item.is_active ? 'Aktif' : 'Nonaktif'" :severity="item.is_active ? 'success' : 'secondary'" />
                        </div>
                        <div v-if="reorderMode" class="flex gap-2">
                            <Button label="Naik" icon="pi pi-arrow-up" severity="secondary" outlined class="min-h-11 flex-1" :disabled="index === 0 || isBusy" @click="moveItem(index, 'up')" />
                            <Button label="Turun" icon="pi pi-arrow-down" severity="secondary" outlined class="min-h-11 flex-1" :disabled="index === visibleItems.length - 1 || isBusy" @click="moveItem(index, 'down')" />
                        </div>
                        <div v-else class="grid grid-cols-2 gap-2">
                            <Button v-if="can.update" label="Edit" icon="pi pi-pencil" severity="secondary" outlined class="min-h-11" :disabled="isBusy" @click="openEdit(item, $event)" />
                            <Button v-if="can.update_status" :label="item.is_active ? 'Nonaktifkan' : 'Aktifkan'" :icon="item.is_active ? 'pi pi-ban' : 'pi pi-check'" severity="secondary" outlined class="min-h-11" :loading="statusUpdatingId === item.id" @click="toggleStatus(item)" />
                            <Button
                                v-if="can.delete"
                                label="Hapus"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                class="min-h-11"
                                :disabled="item.pembandings_count > 0 || isBusy"
                                :title="item.pembandings_count > 0 ? 'Nonaktifkan data yang sudah digunakan.' : 'Hapus permanen'"
                                @click="deleteItem(item)"
                            />
                        </div>
                    </article>
                </div>
            </template>
        </UiSurface>

        <Dialog
            :visible="dialogVisible"
            modal
            :draggable="false"
            :closable="!submitting"
            :close-on-escape="!submitting"
            :dismissable-mask="false"
            :header="isEditing ? `Edit ${category.label}` : `Tambah ${category.label}`"
            class="master-data-dialog"
            style="width: min(560px, 100%)"
            :breakpoints="{ '640px': '100vw' }"
            @update:visible="requestClose"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <UiField id="master_name" :label="`Nama ${category.label}`" required :error="errors.name">
                    <InputText
                        id="master_name"
                        v-model="form.name"
                        class="w-full"
                        :placeholder="`Masukkan nama ${category.label.toLowerCase()}`"
                        :invalid="Boolean(errors.name)"
                        :aria-describedby="errors.name ? 'master_name_error' : undefined"
                        autofocus
                    />
                </UiField>

                <div v-if="supportsBadgeColor" class="grid gap-4 sm:grid-cols-[120px_1fr]">
                    <UiField id="master_badge" label="Warna Badge" :error="errors.badge_color" help="Gunakan format warna heksadesimal, misalnya #64748b.">
                        <input
                            id="master_badge"
                            :value="form.badge_color || '#64748b'"
                            type="color"
                            class="h-11 w-full cursor-pointer rounded-lg border border-slate-200 bg-white p-1"
                            @input="form.badge_color = $event.target.value"
                        />
                    </UiField>
                    <UiField id="master_badge_text" label="Kode Warna" :error="errors.badge_color">
                        <InputText id="master_badge_text" v-model="form.badge_color" class="w-full font-mono" placeholder="#64748b" />
                    </UiField>
                </div>

                <UiField v-if="supportsMarkerIcon" id="master_marker" label="URL Ikon Marker" :error="errors.marker_icon_url" help="Opsional. Masukkan URL HTTPS untuk ikon peta.">
                    <InputText id="master_marker" v-model="form.marker_icon_url" class="w-full" placeholder="https://..." />
                </UiField>

                <p v-if="errors.general" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert">
                    {{ errors.general }}
                </p>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <Button type="button" label="Batal" severity="secondary" outlined class="min-h-11" :disabled="submitting" @click="requestClose(false)" />
                    <Button type="submit" :label="isEditing ? 'Simpan Perubahan' : `Tambah ${category.label}`" icon="pi pi-save" class="min-h-11" :loading="submitting" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<style>
@media (max-width: 640px) {
    .master-data-dialog {
        height: 100dvh !important;
        max-height: 100dvh !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    .master-data-dialog .p-dialog-content {
        flex: 1;
    }
}
</style>
