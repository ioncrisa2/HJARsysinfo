<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import DictionaryCrud from "../../components/master-data/DictionaryCrud.vue";
import LocationManager from "../../components/master-data/LocationManager.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import UiSectionHeader from "../../components/ui/UiSectionHeader.vue";
import { useToast } from "primevue/usetoast";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    dictionaries: { type: Array, default: () => [] },
    locationMeta: { type: Array, default: () => [] },
});

const toast = useToast();

const view = ref("dictionary"); // dictionary | location
const selectedDictionaryType = ref(props.dictionaries?.[0]?.type ?? null);

const selectedDictionary = computed(() =>
    (props.dictionaries ?? []).find((d) => d.type === selectedDictionaryType.value) ?? null,
);

const handleSuccess = (message = "Berhasil disimpan") => {
    toast.add({ severity: "success", summary: message, life: 2500 });
};

const handleError = (message = "Terjadi kesalahan") => {
    toast.add({ severity: "error", summary: message, life: 3000 });
};
</script>

<template>
    <Head title="Master Data" />

    <div class="grid gap-4 py-3 sm:py-5 lg:grid-cols-[280px_1fr]">
        <UiSurface class="h-fit" padding="none">
            <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <UiSectionHeader
                    title="Master Data"
                    subtitle="Kelola kamus dan data lokasi."
                    icon="pi pi-sliders-h"
                />
            </div>

            <div class="p-2">
                <button
                    type="button"
                    class="w-full rounded-[var(--radius-md)] px-3 py-2 text-left text-sm font-semibold"
                    :class="view === 'dictionary' ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50'"
                    @click="view = 'dictionary'"
                >
                    <span class="flex items-center gap-2">
                        <i class="pi pi-database text-[12px] text-amber-600" aria-hidden="true" />
                        Kamus
                    </span>
                </button>

                <div v-if="view === 'dictionary'" class="mt-2 space-y-1">
                    <button
                        v-for="dict in props.dictionaries"
                        :key="dict.type"
                        type="button"
                        class="w-full rounded-[var(--radius-md)] px-3 py-2 text-left text-sm font-medium"
                        :class="selectedDictionaryType === dict.type
                            ? 'bg-white shadow-sm border border-slate-200 text-slate-900'
                            : 'text-slate-700 hover:bg-slate-50'"
                        @click="selectedDictionaryType = dict.type"
                    >
                        <span class="flex items-center gap-2">
                            <i :class="`pi ${dict.icon}`" class="text-[12px] text-slate-500" aria-hidden="true" />
                            <span class="truncate">{{ dict.label }}</span>
                        </span>
                    </button>
                </div>

                <div class="mt-3 border-t border-slate-100 pt-3">
                    <button
                        type="button"
                        class="w-full rounded-[var(--radius-md)] px-3 py-2 text-left text-sm font-semibold"
                        :class="view === 'location' ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50'"
                        @click="view = 'location'"
                    >
                        <span class="flex items-center gap-2">
                            <i class="pi pi-map text-[12px] text-amber-600" aria-hidden="true" />
                            Data Lokasi
                        </span>
                    </button>
                </div>
            </div>
        </UiSurface>

        <div class="min-w-0">
            <DictionaryCrud
                v-if="view === 'dictionary' && selectedDictionary"
                :type="selectedDictionary.type"
                :label="selectedDictionary.label"
                :icon="selectedDictionary.icon"
                :extra="selectedDictionary.extra || []"
                @success="handleSuccess"
                @error="handleError"
            />

            <LocationManager v-else @success="handleSuccess" @error="handleError" />
        </div>
    </div>
</template>

