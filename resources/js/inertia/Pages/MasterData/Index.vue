<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import Tabs from "primevue/tabs";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import TabPanels from "primevue/tabpanels";
import TabPanel from "primevue/tabpanel";
import DictionaryCrud from "../../components/master-data/DictionaryCrud.vue";
import LocationManager from "../../components/master-data/LocationManager.vue";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import { useToast } from "primevue/usetoast";

defineOptions({ layout: TopNavLayout });

const props = defineProps({
    dictionaries: { type: Array, default: () => [] },
    locationMeta: { type: Array, default: () => [] },
});

const activeTab = ref("kamus");
const toast     = useToast();

const visitedTabs = ref(new Set(["kamus"])); // "kamus" is the default tab, mount immediately

const onTabChange = (value) => {
    visitedTabs.value.add(value);
};

const handleSuccess = (message = "Berhasil disimpan") => {
    toast.add({ severity: "success", summary: message, life: 2500 });
};

const handleError = (message = "Terjadi kesalahan") => {
    toast.add({ severity: "error", summary: message, life: 3000 });
};
</script>

<template>
    <Head title="Master Data" />

    <div class="space-y-4 py-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Pengaturan</p>
                <h1 class="text-xl font-bold text-slate-900">Master Data</h1>
                <p class="text-sm text-slate-400">Kamus & data lokasi untuk pembanding.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <Tabs v-model:value="activeTab" @update:value="onTabChange">
                <TabList class="border-b border-slate-100 bg-slate-50/60 px-4">
                    <Tab value="kamus">
                        <div class="flex items-center gap-2 py-2">
                            <i class="pi pi-database text-xs" />
                            <span class="text-sm font-semibold">Kamus</span>
                        </div>
                    </Tab>
                    <Tab value="lokasi">
                        <div class="flex items-center gap-2 py-2">
                            <i class="pi pi-map text-xs" />
                            <span class="text-sm font-semibold">Data Lokasi</span>
                        </div>
                    </Tab>
                </TabList>

                <TabPanels>
                    <!--
                        v-if="visitedTabs.has('kamus')" — mount only when first visited
                        v-show inside TabPanel handles hide/show after that
                        This prevents all 9 DictionaryCrud components from firing
                        API calls before the user has even seen the tab.
                    -->
                    <TabPanel value="kamus">
                        <div v-if="visitedTabs.has('kamus')" class="grid gap-4 p-4">
                            <DictionaryCrud
                                v-for="dict in props.dictionaries"
                                :key="dict.type"
                                :type="dict.type"
                                :label="dict.label"
                                :icon="dict.icon"
                                :extra="dict.extra || []"
                                @success="handleSuccess"
                                @error="handleError"
                            />
                        </div>
                    </TabPanel>

                    <TabPanel value="lokasi">
                        <div v-if="visitedTabs.has('lokasi')" class="p-4">
                            <LocationManager
                                @success="handleSuccess"
                                @error="handleError"
                            />
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </div>
</template>
