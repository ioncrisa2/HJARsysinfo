<script setup>
defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <div
            v-for="section in sections"
            :key="section.title"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
            <!-- Section header -->
            <div class="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                    <i :class="`pi ${section.icon} text-amber-600`" style="font-size: 12px" />
                </div>
                <span class="text-sm font-bold text-slate-700">{{ section.title }}</span>
            </div>

            <!-- Grid layout (Spesifikasi) -->
            <template v-if="section.grid">
                <dl class="grid grid-cols-2 gap-px bg-slate-100 sm:grid-cols-3">
                    <div
                        v-for="item in section.items"
                        :key="item.label"
                        class="flex flex-col gap-1 bg-white px-4 py-3"
                    >
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ item.label }}
                        </dt>
                        <dd
                            class="text-sm font-bold"
                            :class="item.value === 'n/a' ? 'text-slate-300' : 'text-slate-800'"
                        >
                            {{ item.value }}
                        </dd>
                    </div>
                </dl>
            </template>

            <!-- List layout -->
            <template v-else>
                <dl class="divide-y divide-slate-50">
                    <div
                        v-for="(item, idx) in section.items"
                        :key="item.label"
                        class="px-4 py-2.5 text-sm"
                        :class="[
                            item.full ? 'space-y-1.5' : 'flex items-start justify-between gap-4',
                            idx % 2 === 1 ? 'bg-slate-50/50' : '',
                        ]"
                    >
                        <dt class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ item.label }}
                        </dt>
                        <dd
                            class="min-w-0 wrap-break-words font-semibold"
                            :class="[
                                item.highlight
                                    ? 'text-amber-600 text-base font-black'
                                    : item.value === 'n/a'
                                        ? 'text-slate-300'
                                        : 'text-slate-700',
                                item.full ? '' : 'text-right',
                            ]"
                        >
                            {{ item.value ?? "n/a" }}
                        </dd>
                    </div>
                </dl>
            </template>
        </div>
    </div>
</template>
