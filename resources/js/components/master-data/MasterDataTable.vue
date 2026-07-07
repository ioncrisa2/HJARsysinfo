<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import Tag from "primevue/tag";
import UiEmptyState from "../../components/ui/UiEmptyState.vue";

const props = defineProps({
    rows: { type: Array, required: true },
    records: { type: Object, required: true },
    supportsBadgeColor: { type: Boolean, default: false },
    canReorder: { type: Boolean, default: false },
    canSelect: { type: Boolean, default: false },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canToggleStatus: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    draggedId: { type: [Number, String], default: null },
    selectedIds: { type: Array, default: () => [] },
    allVisibleSelected: { type: Boolean, default: false },
});

const emit = defineEmits([
    "toggleAllVisible",
    "toggleSelected",
    "toggleStatus",
    "openEdit",
    "deleteRecord",
    "onDragStart",
    "onDrop",
    "openCreate",
]);

const formatStatusSeverity = (active) => (active ? "success" : "danger");
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[880px] text-left text-sm">
            <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                <tr>
                    <th v-if="canSelect" class="w-12 px-5 py-4">
                        <button
                            type="button"
                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                            aria-label="Pilih semua data terlihat"
                            @click="emit('toggleAllVisible')"
                        >
                            <i v-if="allVisibleSelected" class="pi pi-check text-[10px] text-slate-700" />
                        </button>
                    </th>
                    <th class="w-16 px-5 py-4">Urut</th>
                    <th class="px-5 py-4">Nama / Slug</th>
                    <th class="px-5 py-4">Status</th>
                    <th v-if="supportsBadgeColor" class="px-5 py-4">Badge</th>
                    <th v-if="canUpdate || canDelete" class="px-5 py-4 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                <tr
                    v-for="record in rows"
                    :key="record.id"
                    class="group hover:bg-slate-50"
                    :class="draggedId === record.id ? 'opacity-50' : ''"
                    :draggable="canReorder"
                    @dragstart="emit('onDragStart', record)"
                    @dragover.prevent
                    @drop="emit('onDrop', record)"
                >
                    <td v-if="canSelect" class="px-5 py-4">
                        <button
                            type="button"
                            class="flex size-5 items-center justify-center rounded border border-slate-300 bg-white"
                            :aria-label="`Pilih ${record.name}`"
                            @click="emit('toggleSelected', record.id)"
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
                        <button type="button" class="text-left" :disabled="!canToggleStatus" @click="canToggleStatus && emit('toggleStatus', record)">
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
                    <td v-if="canUpdate || canDelete" class="px-5 py-4">
                        <div class="flex justify-end gap-1">
                            <Button v-if="canUpdate" icon="pi pi-pencil" text rounded severity="secondary" aria-label="Edit" @click="emit('openEdit', record)" />
                            <Button v-if="canDelete" icon="pi pi-trash" text rounded severity="danger" aria-label="Hapus" @click="emit('deleteRecord', record)" />
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
                                <Button v-if="canCreate" label="Tambah Data" icon="pi pi-plus" size="small" @click="emit('openCreate')" />
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
</template>
