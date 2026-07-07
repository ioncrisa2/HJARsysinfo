<script setup>
import { computed } from "vue";
import UiEmptyState from "../../ui/UiEmptyState.vue";
import UiSurface from "../../ui/UiSurface.vue";
import UiSectionHeader from "../../ui/UiSectionHeader.vue";

const props = defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
});

const hasActivities = computed(() => (props.activities ?? []).length > 0);

const eventMeta = (event) => {
    if (event === "created") {
        return { label: "Dibuat", icon: "pi pi-plus-circle", pill: "border-slate-200 bg-white text-slate-700", dot: "bg-slate-400" };
    }
    if (event === "deleted") {
        return { label: "Dihapus", icon: "pi pi-trash", pill: "border-red-200 bg-red-50 text-red-700", dot: "bg-red-500" };
    }
    return { label: "Diubah", icon: "pi pi-pencil", pill: "border-amber-200 bg-amber-50 text-amber-800", dot: "bg-amber-500" };
};

const hasValue = (v) => v !== null && v !== undefined && `${v}`.trim() !== "";
</script>

<template>
    <UiEmptyState
        v-if="!hasActivities"
        title="Belum ada riwayat"
        description="Aktivitas perubahan pada data ini akan muncul di sini."
        icon="pi pi-history"
    />

    <UiSurface v-else padding="none" class="overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <UiSectionHeader
                title="Aktivitas"
                :subtitle="`Total ${activities.length} item`"
                icon="pi pi-list"
            />
        </div>

        <ul class="divide-y divide-slate-100">
            <li v-for="activity in activities" :key="activity.id" class="px-4 py-4">
                <details class="group">
                    <summary class="cursor-pointer list-none">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
                                        :class="eventMeta(activity.event).pill"
                                    >
                                        <span class="size-1.5 rounded-full" :class="eventMeta(activity.event).dot" aria-hidden="true" />
                                        <i :class="eventMeta(activity.event).icon" class="text-[12px]" aria-hidden="true" />
                                        {{ eventMeta(activity.event).label }}
                                    </span>

                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                                        <span class="inline-flex size-5 items-center justify-center rounded-full bg-slate-100">
                                            <i class="pi pi-user text-[10px] text-slate-500" aria-hidden="true" />
                                        </span>
                                        <span class="truncate">{{ activity.causer ?? "Sistem" }}</span>
                                    </span>

                                    <span v-if="activity.causer_email" class="truncate text-xs text-slate-500">
                                        {{ activity.causer_email }}
                                    </span>
                                </div>

                                <p class="text-pretty text-xs text-slate-500">
                                    <span class="ui-tabular font-semibold text-slate-700">
                                        {{ (activity.changes?.length ?? 0).toLocaleString("id-ID") }}
                                    </span>
                                    field berubah.
                                    <span class="text-slate-400">Klik untuk melihat detail.</span>
                                </p>
                            </div>

                            <span class="ui-tabular flex items-center gap-2 text-xs font-medium text-slate-500">
                                <i class="pi pi-clock text-[11px]" aria-hidden="true" />
                                {{ activity.created_at }}
                            </span>
                        </div>
                    </summary>

                    <div class="mt-4">
                        <div v-if="activity.changes?.length" class="overflow-hidden rounded-[var(--radius-lg)] border border-slate-200 bg-white">
                            <div class="grid grid-cols-[1.2fr_1fr_1fr] border-b border-slate-100 bg-slate-50/70">
                                <div class="px-3 py-2 text-xs font-semibold text-slate-600">Field</div>
                                <div class="px-3 py-2 text-xs font-semibold text-slate-600">Sebelumnya</div>
                                <div class="px-3 py-2 text-xs font-semibold text-slate-600">Sesudahnya</div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                <div
                                    v-for="change in activity.changes"
                                    :key="change.field"
                                    class="grid grid-cols-[1.2fr_1fr_1fr] text-xs"
                                >
                                    <div class="min-w-0 px-3 py-2.5 font-semibold text-slate-700">
                                        <span class="truncate">{{ change.field }}</span>
                                    </div>

                                    <div class="ui-tabular min-w-0 px-3 py-2.5 text-slate-700">
                                        <span v-if="hasValue(change.old)" class="line-through decoration-slate-300">
                                            {{ change.old }}
                                        </span>
                                        <span v-else class="text-slate-400">&mdash;</span>
                                    </div>

                                    <div class="ui-tabular min-w-0 px-3 py-2.5 font-semibold text-slate-900">
                                        <span v-if="hasValue(change.new)">{{ change.new }}</span>
                                        <span v-else class="font-medium text-slate-400">&mdash;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-pretty text-xs text-slate-500">
                            Tidak ada detail perubahan yang tercatat.
                        </p>
                    </div>
                </details>
            </li>
        </ul>

        <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3 text-center text-xs text-slate-500">
            Akhir dari riwayat.
        </div>
    </UiSurface>
</template>

