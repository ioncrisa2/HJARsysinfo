<script setup>
import { Head, Link } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    activities: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({
            view_detail: false,
        }),
    },
});

const formatValue = (value) => {
    if (value === null || value === undefined || value === "") {
        return "-";
    }

    if (typeof value === "object") {
        try {
            return JSON.stringify(value);
        } catch {
            return "[object]";
        }
    }

    return String(value);
};

const eventLabel = (value) => {
    if (!value) return "activity";

    return String(value)
        .replace(/_/g, " ")
        .toLowerCase();
};
</script>

<template>
    <Head :title="`Riwayat ${record.comparable_code ?? ''}`" />

    <div class="space-y-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Audit Trail</p>
                <h1 class="text-xl font-bold text-slate-900">Riwayat Perubahan</h1>
                <p class="text-sm text-slate-500">{{ record.comparable_code }} - {{ record.unit_name }}</p>
            </div>

            <div class="flex gap-2">
                <Link href="/home/non-properti" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                    Daftar
                </Link>
                <Link
                    v-if="can.view_detail"
                    :href="`/home/non-properti/${record.id}`"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                >
                    Detail
                </Link>
            </div>
        </div>

        <div v-if="activities.length" class="space-y-3">
            <div
                v-for="activity in activities"
                :key="activity.id"
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ eventLabel(activity.event || activity.description) }}</p>
                        <p class="text-xs text-slate-500">
                            {{ activity.causer || "Sistem" }}
                            <span v-if="activity.causer_email">({{ activity.causer_email }})</span>
                        </p>
                    </div>
                    <p class="text-xs text-slate-500">{{ activity.created_at || "-" }}</p>
                </div>

                <div class="mt-3 overflow-x-auto" v-if="activity.changes?.length">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-500">
                                <th class="px-2 py-1 text-left">Field</th>
                                <th class="px-2 py-1 text-left">Sebelum</th>
                                <th class="px-2 py-1 text-left">Sesudah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(change, idx) in activity.changes" :key="`${activity.id}-${idx}`" class="border-b border-slate-50">
                                <td class="px-2 py-1 font-mono text-slate-700">{{ change.field }}</td>
                                <td class="px-2 py-1 text-slate-500">{{ formatValue(change.old) }}</td>
                                <td class="px-2 py-1 text-slate-900">{{ formatValue(change.new) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-2 text-xs text-slate-500">Tidak ada detail perubahan field.</p>
            </div>
        </div>

        <div v-else class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
            Belum ada riwayat perubahan.
        </div>
    </div>
</template>