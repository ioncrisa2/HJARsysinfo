<script setup>
defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
});

const eventConfig = (event) => {
    if (event === "created") return {
        color: "text-emerald-700 border-emerald-200 bg-emerald-50",
        dotColor: "bg-emerald-500",
        lineColor: "bg-emerald-100",
        icon: "pi-plus-circle",
        iconColor: "text-emerald-600",
        label: "Dibuat",
    };
    if (event === "deleted") return {
        color: "text-red-700 border-red-200 bg-red-50",
        dotColor: "bg-red-500",
        lineColor: "bg-red-100",
        icon: "pi-trash",
        iconColor: "text-red-600",
        label: "Dihapus",
    };
    return {
        color: "text-amber-700 border-amber-200 bg-amber-50",
        dotColor: "bg-amber-500",
        lineColor: "bg-amber-100",
        icon: "pi-pencil",
        iconColor: "text-amber-600",
        label: event ?? "Diperbarui",
    };
};
</script>

<template>
    <!-- Empty state -->
    <div
        v-if="!activities.length"
        class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-20 text-center"
    >
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
            <i class="pi pi-history text-2xl text-slate-300" />
        </div>
        <div>
            <p class="text-sm font-bold text-slate-600">Belum ada riwayat</p>
            <p class="text-xs text-slate-400 mt-0.5">Aktivitas perubahan pada data ini akan muncul di sini.</p>
        </div>
    </div>

    <!-- Timeline -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <ul class="relative">
            <!-- Vertical timeline line -->
            <div class="absolute left-[2.35rem] top-0 bottom-0 w-px bg-slate-100 z-0" />

            <li
                v-for="(activity, idx) in activities"
                :key="activity.id"
                class="relative px-5 py-5 transition-colors hover:bg-slate-50/60"
                :class="idx !== activities.length - 1 ? 'border-b border-slate-100' : ''"
            >
                <div class="flex items-start gap-4">

                    <!-- Timeline dot -->
                    <div class="relative z-10 mt-0.5 flex-shrink-0">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white shadow-sm"
                            :class="eventConfig(activity.event).dotColor"
                        >
                            <i
                                :class="`pi ${eventConfig(activity.event).icon} text-white`"
                                style="font-size: 10px"
                            />
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">

                        <!-- Event header -->
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Event badge -->
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide"
                                    :class="eventConfig(activity.event).color"
                                >
                                    {{ eventConfig(activity.event).label }}
                                </span>

                                <!-- Causer -->
                                <div class="flex items-center gap-1.5">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 shrink-0">
                                        <i class="pi pi-user text-slate-500" style="font-size: 9px" />
                                    </div>
                                    <span class="text-sm font-semibold text-slate-800">
                                        {{ activity.causer ?? "Sistem" }}
                                    </span>
                                    <span v-if="activity.causer_email" class="text-xs text-slate-400">
                                        ({{ activity.causer_email }})
                                    </span>
                                </div>
                            </div>

                            <!-- Timestamp -->
                            <span class="flex items-center gap-1 text-xs text-slate-400 shrink-0">
                                <i class="pi pi-clock text-[10px]" />
                                {{ activity.created_at }}
                            </span>
                        </div>

                        <!-- Changes diff table -->
                        <div
                            v-if="activity.changes?.length"
                            class="overflow-hidden rounded-xl border border-slate-100"
                        >
                            <!-- Table header -->
                            <div class="grid grid-cols-[1.4fr_1fr_1fr] bg-slate-50 border-b border-slate-100">
                                <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                    Field
                                </div>
                                <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                    Sebelumnya
                                </div>
                                <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                    Menjadi
                                </div>
                            </div>

                            <!-- Table rows -->
                            <div class="divide-y divide-slate-50 bg-white">
                                <div
                                    v-for="change in activity.changes"
                                    :key="change.field"
                                    class="grid grid-cols-[1.4fr_1fr_1fr] text-xs hover:bg-slate-50/80 transition-colors"
                                >
                                    <!-- Field name -->
                                    <div class="px-3 py-2.5 font-semibold text-slate-600 break-all">
                                        {{ change.field }}
                                    </div>
                                    <!-- Old value -->
                                    <div class="px-3 py-2.5 break-all">
                                        <span
                                            v-if="change.old !== null && change.old !== undefined && change.old !== ''"
                                            class="rounded bg-red-50 px-1.5 py-0.5 text-red-600 font-medium line-through decoration-red-300"
                                        >
                                            {{ change.old }}
                                        </span>
                                        <span v-else class="text-slate-300 italic">kosong</span>
                                    </div>
                                    <!-- New value -->
                                    <div class="px-3 py-2.5 break-all">
                                        <span
                                            v-if="change.new !== null && change.new !== undefined && change.new !== ''"
                                            class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700 font-semibold"
                                        >
                                            {{ change.new }}
                                        </span>
                                        <span v-else class="text-slate-300 italic">kosong</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Changes count footer -->
                            <div class="border-t border-slate-100 bg-slate-50/60 px-3 py-1.5 text-right text-[10px] font-semibold text-slate-400">
                                {{ activity.changes.length }} field diubah
                            </div>
                        </div>

                        <!-- No changes note -->
                        <p v-else class="text-xs text-slate-400 italic">
                            Tidak ada detail perubahan yang tercatat.
                        </p>

                    </div>
                </div>
            </li>
        </ul>

        <!-- List footer -->
        <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-3 text-center text-xs text-slate-400">
            Menampilkan <strong class="text-slate-600">{{ activities.length }}</strong> aktivitas &mdash; akhir dari riwayat
        </div>
    </div>
</template>
