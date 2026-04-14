<script setup>
import { computed } from "vue";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import TabPanel from "primevue/tabpanel";
import TabPanels from "primevue/tabpanels";
import Tabs from "primevue/tabs";
import UiSurface from "../../ui/UiSurface.vue";
import PembandingGeneralTab from "./tabs/PembandingGeneralTab.vue";
import PembandingLocationTab from "./tabs/PembandingLocationTab.vue";
import PembandingPropertyTab from "./tabs/PembandingPropertyTab.vue";
import PembandingNotesTab from "./tabs/PembandingNotesTab.vue";

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    activeTab: { type: String, default: "umum" },
    mode: { type: String, default: "create" },
    regencyOptions: { type: Array, default: () => [] },
    districtOptions: { type: Array, default: () => [] },
    villageOptions: { type: Array, default: () => [] },
    imagePreview: { type: String, default: null },
    isTanah: { type: Boolean, default: false },
    bangunanRequired: { type: Boolean, default: true },
    numConfig: { type: Object, default: () => ({}) },
    currencyConfig: { type: Object, default: () => ({}) },
    handleImageUpload: { type: Function, default: null },
    clearImage: { type: Function, default: null },
});

const emit = defineEmits(["update:activeTab", "submit", "submit-and-create-another"]);

const activeTabModel = computed({
    get: () => props.activeTab,
    set: (value) => emit("update:activeTab", value),
});

const tabErrorKeys = {
    umum: [
        "jenis_listing_id",
        "jenis_objek_id",
        "nama_pemberi_informasi",
        "nomer_telepon_pemberi_informasi",
        "status_pemberi_informasi_id",
        "tanggal_data",
    ],
    lokasi: ["alamat_data", "province_id", "regency_id", "district_id", "village_id", "latitude", "longitude"],
    properti: [
        "image",
        "luas_tanah",
        "luas_bangunan",
        "lebar_depan",
        "lebar_jalan",
        "tahun_bangun",
        "rasio_tapak",
        "bentuk_tanah_id",
        "posisi_tanah_id",
        "kondisi_tanah_id",
        "topografi_id",
        "dokumen_tanah_id",
        "peruntukan_id",
        "harga",
    ],
    catatan: ["catatan"],
};

const tabErrorCount = computed(() => {
    const errors = props.form.errors ?? {};
    const result = {};
    for (const [tab, keys] of Object.entries(tabErrorKeys)) {
        result[tab] = keys.filter((k) => errors[k]).length;
    }
    return result;
});

const tabs = [
    { value: "umum", label: "Umum", icon: "pi-info-circle" },
    { value: "lokasi", label: "Lokasi", icon: "pi-map-marker" },
    { value: "properti", label: "Properti", icon: "pi-building" },
    { value: "catatan", label: "Catatan", icon: "pi-file-edit" },
];
</script>

<template>
    <UiSurface padding="none" class="overflow-hidden">
        <Tabs v-model:value="activeTabModel">
            <TabList class="border-b border-slate-100 bg-slate-50/70 px-2 sm:px-3">
                <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
                    <div class="relative flex items-center gap-2 px-2 py-2 text-sm font-semibold text-slate-700">
                        <i :class="`pi ${tab.icon}`" class="text-[12px] text-slate-500" aria-hidden="true" />
                        <span>{{ tab.label }}</span>
                        <span
                            v-if="tabErrorCount[tab.value] > 0"
                            class="ml-1 inline-flex items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white"
                            :aria-label="`${tabErrorCount[tab.value]} error di tab ${tab.label}`"
                        >
                            {{ tabErrorCount[tab.value] }}
                        </span>
                    </div>
                </Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="umum">
                    <PembandingGeneralTab :form="form" :options="options" @next="activeTabModel = 'lokasi'" />
                </TabPanel>
                <TabPanel value="lokasi">
                    <PembandingLocationTab
                        :form="form"
                        :options="options"
                        :regency-options="regencyOptions"
                        :district-options="districtOptions"
                        :village-options="villageOptions"
                        @prev="activeTabModel = 'umum'"
                        @next="activeTabModel = 'properti'"
                    />
                </TabPanel>
                <TabPanel value="properti">
                    <PembandingPropertyTab
                        :form="form"
                        :options="options"
                        :mode="mode"
                        :image-preview="imagePreview"
                        :is-tanah="isTanah"
                        :bangunan-required="bangunanRequired"
                        :num-config="numConfig"
                        :currency-config="currencyConfig"
                        :handle-image-upload="handleImageUpload"
                        :clear-image="clearImage"
                        @prev="activeTabModel = 'lokasi'"
                        @next="activeTabModel = 'catatan'"
                    />
                </TabPanel>
                <TabPanel value="catatan">
                    <PembandingNotesTab
                        :form="form"
                        :mode="mode"
                        @prev="activeTabModel = 'properti'"
                        @submit="emit('submit')"
                        @submit-and-create-another="emit('submit-and-create-another')"
                    />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </UiSurface>
</template>

