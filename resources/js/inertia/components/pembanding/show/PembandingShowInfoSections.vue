<script setup>
import UiSurface from "../../ui/UiSurface.vue";
import UiSectionHeader from "../../ui/UiSectionHeader.vue";

defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="space-y-4">
        <UiSurface
            v-for="section in sections"
            :key="section.title"
            padding="none"
            class="overflow-hidden"
        >
            <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <UiSectionHeader :title="section.title" :icon="`pi ${section.icon}`" />
            </div>

            <div v-if="section.grid" class="p-4">
                <dl class="ui-tabular grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div v-for="item in section.items" :key="item.label" class="min-w-0">
                        <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                        <dd
                            class="mt-1 break-words text-sm font-semibold"
                            :class="item.value === 'n/a' ? 'text-slate-400 font-medium' : 'text-slate-900'"
                        >
                            {{ item.value }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div v-else class="p-4">
                <dl class="space-y-3">
                    <div
                        v-for="item in section.items"
                        :key="item.label"
                        class="grid gap-1.5"
                        :class="item.full ? '' : 'sm:grid-cols-12 sm:items-start sm:gap-4'"
                    >
                        <dt
                            class="text-xs font-medium text-slate-500"
                            :class="item.full ? '' : 'sm:col-span-4'"
                        >
                            {{ item.label }}
                        </dt>
                        <dd
                            class="ui-tabular min-w-0 break-words text-sm font-semibold"
                            :class="[
                                item.highlight ? 'text-amber-700 font-black' : 'text-slate-900',
                                item.value === 'n/a' ? 'text-slate-400 font-medium' : '',
                                item.full ? '' : 'sm:col-span-8 sm:text-right',
                            ]"
                        >
                            {{ item.value ?? "n/a" }}
                        </dd>
                    </div>
                </dl>
            </div>
        </UiSurface>
    </div>
</template>

