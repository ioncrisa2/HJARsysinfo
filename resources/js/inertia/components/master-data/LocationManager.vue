<script setup>
import { reactive, ref, watch } from "vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Message from "primevue/message";
import { apiRequest } from "../../utils/apiRequest";
import { useLocationSearch } from "../../composables/useLocationSearch";

const emit = defineEmits(["success", "error"]);

// ─── Shared data ──────────────────────────────────────────────────────────────
const error     = ref(null);
const provinces = ref([]);

// Per-level data lists
const regencies = ref([]);
const districts = ref([]);

// ─── Per-form loading states ──────────────────────────────────────────────────
const loading = reactive({
    provinces : false,
    regency   : false,
    district  : false,
    village   : false,
});

// ─── Forms ────────────────────────────────────────────────────────────────────
const forms = reactive({
    province : { id: "", name: "" },
    regency  : { province_id: null, name: "" },
    district : { regency_id: null, name: "" },
    village  : { province_id: null, regency_id: null, district_id: null, name: "" },
});

const toErrorMessage = (err, fallback) => err?.message || fallback;

// ─── Province ─────────────────────────────────────────────────────────────────
const loadProvinces = async () => {
    loading.provinces = true;
    try {
        provinces.value = await apiRequest("/home/master-data/locations/provinces");
        error.value = null;
    } catch (err) {
        provinces.value = [];
        error.value = toErrorMessage(err, "Gagal memuat provinsi");
        emit("error", error.value);
    } finally {
        loading.provinces = false;
    }
};

const submitProvince = async () => {
    loading.provinces = true;
    error.value = null;
    try {
        await apiRequest("/home/master-data/locations/provinces", {
            method: "POST",
            body: forms.province,
        });
        forms.province.id   = "";
        forms.province.name = "";
        await loadProvinces();
        emit("success", "Provinsi ditambahkan");
    } catch (err) {
        error.value = toErrorMessage(err, "Gagal simpan provinsi");
        emit("error", error.value);
    } finally {
        loading.provinces = false;
    }
};

const resetProvince = () => {
    forms.province.id   = "";
    forms.province.name = "";
};

// ─── Regency ──────────────────────────────────────────────────────────────────
/**
 * Raw loader — called by useLocationSearch internally.
 * `params` may contain { q, province_id }.
 */
const fetchRegencies = async (params = {}) => {
    const url = new URLSearchParams();
    if (params.province_id) url.set("province_id", params.province_id);
    if (params.q)           url.set("q", params.q);
    // No hard cap when filtering by parent; safety cap for unfiltered calls
    url.set("per_page", params.province_id ? "999" : "200");

    regencies.value = await apiRequest(`/home/master-data/locations/regencies?${url.toString()}`);
};

const regencySearch = useLocationSearch(fetchRegencies);

const loadRegencies = (provinceId, q = "") => {
    regencySearch.query.value = q;
    regencySearch.load({ province_id: provinceId });
};

const submitRegency = async () => {
    loading.regency = true;
    error.value     = null;
    try {
        await apiRequest("/home/master-data/locations/regencies", {
            method: "POST",
            body: forms.regency,
        });
        forms.regency.name = "";
        await loadRegencies(forms.regency.province_id);
        emit("success", "Kabupaten/Kota ditambahkan");
    } catch (err) {
        error.value = toErrorMessage(err, "Gagal simpan kab/kota");
        emit("error", error.value);
    } finally {
        loading.regency = false;
    }
};

const resetRegency = () => {
    forms.regency.province_id = null;
    forms.regency.name        = "";
};

// ─── District ─────────────────────────────────────────────────────────────────
const fetchDistricts = async (params = {}) => {
    const url = new URLSearchParams();
    if (params.regency_id) url.set("regency_id", params.regency_id);
    if (params.q)          url.set("q", params.q);
    url.set("per_page", params.regency_id ? "999" : "200");

    districts.value = await apiRequest(`/home/master-data/locations/districts?${url.toString()}`);
};

const districtSearch = useLocationSearch(fetchDistricts);

const loadDistricts = (regencyId, q = "") => {
    districtSearch.query.value = q;
    districtSearch.load({ regency_id: regencyId });
};

const submitDistrict = async () => {
    loading.district = true;
    error.value      = null;
    try {
        await apiRequest("/home/master-data/locations/districts", {
            method: "POST",
            body: forms.district,
        });
        forms.district.name = "";
        await loadDistricts(forms.district.regency_id);
        emit("success", "Kecamatan ditambahkan");
    } catch (err) {
        error.value = toErrorMessage(err, "Gagal simpan kecamatan");
        emit("error", error.value);
    } finally {
        loading.district = false;
    }
};

const resetDistrict = () => {
    forms.district.regency_id = null;
    forms.district.name       = "";
};

// ─── Village ──────────────────────────────────────────────────────────────────
const submitVillage = async () => {
    loading.village = true;
    error.value     = null;
    try {
        await apiRequest("/home/master-data/locations/villages", {
            method: "POST",
            body: forms.village,
        });
        forms.village.name = "";
        emit("success", "Desa/Kelurahan ditambahkan");
    } catch (err) {
        error.value = toErrorMessage(err, "Gagal simpan desa/kelurahan");
        emit("error", error.value);
    } finally {
        loading.village = false;
    }
};

const resetVillage = () => {
    forms.village.province_id = null;
    forms.village.regency_id  = null;
    forms.village.district_id = null;
    forms.village.name        = "";
};

// ─── Village form: cascading watches ─────────────────────────────────────────
// Province → load regencies for both regency form and village form
watch(() => forms.regency.province_id, (val) => {
    if (val) loadRegencies(val);
    else regencies.value = [];
});

watch(() => forms.district.regency_id, (val) => {
    if (val) loadDistricts(val);
    else districts.value = [];
});

watch(() => forms.village.province_id, (val) => {
    forms.village.regency_id  = null;
    forms.village.district_id = null;
    if (val) loadRegencies(val);
    else regencies.value = [];
});

watch(() => forms.village.regency_id, (val) => {
    forms.village.district_id = null;
    if (val) loadDistricts(val);
    else districts.value = [];
});

// ─── Init: only provinces — no unconditional regency preload ─────────────────
loadProvinces();
</script>

<template>
    <div class="grid gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i class="pi pi-map text-amber-500 text-xs" />
                    Data Lokasi
                </div>
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-2">

                <!-- ── Provinsi ── -->
                <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-balance text-sm font-semibold text-slate-800">Provinsi</p>
                    <div class="grid gap-2">
                        <label class="text-xs text-slate-500">Kode (2 digit)</label>
                        <InputText v-model="forms.province.id" class="w-full" placeholder="mis. 11" maxlength="2" />
                        <label class="text-xs text-slate-500">Nama</label>
                        <InputText v-model="forms.province.name" class="w-full" placeholder="NAD" />
                        <div class="flex gap-2">
                            <Button
                                label="Simpan Provinsi"
                                icon="pi pi-save"
                                :loading="loading.provinces"
                                @click="submitProvince"
                            />
                            <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetProvince" />
                        </div>
                    </div>
                </div>

                <!-- ── Kabupaten / Kota ── -->
                <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-balance text-sm font-semibold text-slate-800">Kabupaten / Kota</p>
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500">Cari</label>
                            <InputText
                                v-model="regencySearch.query.value"
                                class="w-full"
                                placeholder="Nama kab/kota"
                                @input="regencySearch.search({ province_id: forms.regency.province_id })"
                            />
                            <Button
                                icon="pi pi-search"
                                severity="secondary"
                                text
                                aria-label="Cari kabupaten/kota"
                                :loading="regencySearch.loading.value"
                                @click="loadRegencies(forms.regency.province_id, regencySearch.query.value)"
                            />
                        </div>
                        <label class="text-xs text-slate-500">Provinsi</label>
                        <Dropdown
                            v-model="forms.regency.province_id"
                            :options="provinces"
                            option-label="name"
                            option-value="id"
                            placeholder="Pilih provinsi"
                            filter
                            class="w-full"
                        />
                        <label class="text-xs text-slate-500">Nama</label>
                        <InputText v-model="forms.regency.name" class="w-full" />
                        <div class="flex gap-2">
                            <Button
                                label="Simpan Kabupaten/Kota"
                                icon="pi pi-save"
                                :loading="loading.regency"
                                :disabled="!forms.regency.province_id"
                                @click="submitRegency"
                            />
                            <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetRegency" />
                        </div>
                    </div>
                </div>

                <!-- ── Kecamatan ── -->
                <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-balance text-sm font-semibold text-slate-800">Kecamatan</p>
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500">Cari</label>
                            <InputText
                                v-model="districtSearch.query.value"
                                class="w-full"
                                placeholder="Nama kecamatan"
                                @input="districtSearch.search({ regency_id: forms.district.regency_id })"
                            />
                            <Button
                                icon="pi pi-search"
                                severity="secondary"
                                text
                                aria-label="Cari kecamatan"
                                :loading="districtSearch.loading.value"
                                @click="loadDistricts(forms.district.regency_id, districtSearch.query.value)"
                            />
                        </div>
                        <label class="text-xs text-slate-500">Kabupaten / Kota</label>
                        <Dropdown
                            v-model="forms.district.regency_id"
                            :options="regencies"
                            option-label="name"
                            option-value="id"
                            placeholder="Pilih kab/kota"
                            filter
                            class="w-full"
                        />
                        <label class="text-xs text-slate-500">Nama</label>
                        <InputText v-model="forms.district.name" class="w-full" />
                        <div class="flex gap-2">
                            <Button
                                label="Simpan Kecamatan"
                                icon="pi pi-save"
                                :loading="loading.district"
                                :disabled="!forms.district.regency_id"
                                @click="submitDistrict"
                            />
                            <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetDistrict" />
                        </div>
                    </div>
                </div>

                <!-- ── Desa / Kelurahan ── -->
                <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <p class="text-balance text-sm font-semibold text-slate-800">Desa / Kelurahan</p>
                    <div class="grid gap-2">
                        <label class="text-xs text-slate-500">Provinsi</label>
                        <Dropdown
                            v-model="forms.village.province_id"
                            :options="provinces"
                            option-label="name"
                            option-value="id"
                            placeholder="Pilih provinsi"
                            filter
                            class="w-full"
                        />
                        <label class="text-xs text-slate-500">Kabupaten / Kota</label>
                        <Dropdown
                            v-model="forms.village.regency_id"
                            :options="regencies"
                            option-label="name"
                            option-value="id"
                            placeholder="Pilih kab/kota"
                            filter
                            class="w-full"
                            :disabled="!forms.village.province_id"
                        />
                        <label class="text-xs text-slate-500">Kecamatan</label>
                        <Dropdown
                            v-model="forms.village.district_id"
                            :options="districts"
                            option-label="name"
                            option-value="id"
                            placeholder="Pilih kecamatan"
                            filter
                            class="w-full"
                            :disabled="!forms.village.regency_id"
                        />
                        <label class="text-xs text-slate-500">Nama</label>
                        <InputText v-model="forms.village.name" class="w-full" />
                        <div class="flex gap-2">
                            <Button
                                label="Simpan Desa/Kelurahan"
                                icon="pi pi-save"
                                :loading="loading.village"
                                :disabled="!forms.village.district_id"
                                @click="submitVillage"
                            />
                            <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetVillage" />
                        </div>
                    </div>
                </div>

            </div>

            <div v-if="error" class="px-4 pb-4">
                <Message severity="error" :closable="false">{{ error }}</Message>
            </div>
        </div>
    </div>
</template>
