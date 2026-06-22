<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiEmptyState from "../../../components/ui/UiEmptyState.vue";
import UiField from "../../../components/ui/UiField.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import { apiRequest } from "../../../utils/apiRequest";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    title: { type: String, default: "Data Lokasi" },
    currentResource: { type: String, default: null },
    resources: { type: Array, default: () => [] },
    records: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({ provinces: [], regencies: [], districts: [] }) },
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();

const resourceUrl = (resource) => `/admin/geo/${resource}`;

const currentMeta = computed(() =>
    (props.resources ?? []).find((resource) => resource.slug === props.currentResource) ?? null,
);

const filterState = reactive({
    search: props.filters?.search ?? "",
    province_id: props.filters?.province_id ?? null,
    regency_id: props.filters?.regency_id ?? null,
    district_id: props.filters?.district_id ?? null,
    per_page: props.filters?.per_page ?? 20,
});

const regencyOptions = ref(props.options?.regencies ?? []);
const districtOptions = ref(props.options?.districts ?? []);
const loadingRegencies = ref(false);
const loadingDistricts = ref(false);

const showForm = ref(false);
const editingRecord = ref(null);

const form = useForm({
    id: "",
    name: "",
    province_id: null,
    regency_id: null,
    district_id: null,
});

const selectedStat = computed(() => props.currentResource ? props.stats?.[props.currentResource] ?? 0 : null);
const hasParentColumn = computed(() => ["regencies", "districts", "villages"].includes(props.currentResource));
const hasChildCount = computed(() => ["provinces", "regencies", "districts"].includes(props.currentResource));

const buildFilterParams = () => {
    const params = {};
    const search = `${filterState.search ?? ""}`.trim();

    if (search) params.search = search;
    if (filterState.per_page) params.per_page = filterState.per_page;

    if (["regencies", "districts", "villages"].includes(props.currentResource) && filterState.province_id) {
        params.province_id = filterState.province_id;
    }

    if (["districts", "villages"].includes(props.currentResource) && filterState.regency_id) {
        params.regency_id = filterState.regency_id;
    }

    if (props.currentResource === "villages" && filterState.district_id) {
        params.district_id = filterState.district_id;
    }

    return params;
};

const applyFilters = () => {
    if (!props.currentResource) return;

    router.get(resourceUrl(props.currentResource), buildFilterParams(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

let searchTimeout = null;
watch(
    () => filterState.search,
    () => {
        if (!props.currentResource) return;
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    },
);

watch(
    () => props.filters,
    (filters) => {
        filterState.search = filters?.search ?? "";
        filterState.province_id = filters?.province_id ?? null;
        filterState.regency_id = filters?.regency_id ?? null;
        filterState.district_id = filters?.district_id ?? null;
        filterState.per_page = filters?.per_page ?? 20;
    },
    { deep: true },
);

watch(
    () => props.options,
    (options) => {
        regencyOptions.value = options?.regencies ?? [];
        districtOptions.value = options?.districts ?? [];
    },
    { deep: true },
);

const loadRegencies = async (provinceId) => {
    regencyOptions.value = [];
    districtOptions.value = [];
    if (!provinceId) return;

    loadingRegencies.value = true;
    try {
        const query = new URLSearchParams({ province_id: provinceId });
        regencyOptions.value = await apiRequest(`/admin/geo/lookups/regencies?${query.toString()}`);
    } finally {
        loadingRegencies.value = false;
    }
};

const loadDistricts = async (regencyId) => {
    districtOptions.value = [];
    if (!regencyId) return;

    loadingDistricts.value = true;
    try {
        const query = new URLSearchParams({ regency_id: regencyId });
        districtOptions.value = await apiRequest(`/admin/geo/lookups/districts?${query.toString()}`);
    } finally {
        loadingDistricts.value = false;
    }
};

const handleProvinceFilterChange = async () => {
    filterState.regency_id = null;
    filterState.district_id = null;
    await loadRegencies(filterState.province_id);
    applyFilters();
};

const handleRegencyFilterChange = async () => {
    filterState.district_id = null;
    await loadDistricts(filterState.regency_id);
    applyFilters();
};

const resetFilters = () => {
    filterState.search = "";
    filterState.province_id = null;
    filterState.regency_id = null;
    filterState.district_id = null;
    applyFilters();
};

const resetForm = () => {
    form.id = "";
    form.name = "";
    form.province_id = null;
    form.regency_id = null;
    form.district_id = null;
    form.clearErrors();
};

const openCreate = async () => {
    if (!props.can.create) return;

    editingRecord.value = null;
    resetForm();
    form.province_id = filterState.province_id ?? null;
    form.regency_id = filterState.regency_id ?? null;
    form.district_id = filterState.district_id ?? null;

    if (form.province_id) await loadRegencies(form.province_id);
    if (form.regency_id) await loadDistricts(form.regency_id);

    showForm.value = true;
};

const openEdit = async (record) => {
    if (!props.can.update) return;

    editingRecord.value = record;
    resetForm();
    form.id = record.id;
    form.name = record.name ?? "";

    if (props.currentResource === "regencies") {
        form.province_id = record.province_id ?? null;
    }

    if (props.currentResource === "districts") {
        form.province_id = record.regency?.province_id ?? null;
        form.regency_id = record.regency_id ?? null;
    }

    if (props.currentResource === "villages") {
        form.province_id = record.district?.regency?.province_id ?? null;
        form.regency_id = record.district?.regency_id ?? null;
        form.district_id = record.district_id ?? null;
    }

    if (form.province_id) await loadRegencies(form.province_id);
    if (form.regency_id) await loadDistricts(form.regency_id);

    showForm.value = true;
};

const handleFormProvinceChange = async () => {
    form.regency_id = null;
    form.district_id = null;
    await loadRegencies(form.province_id);
};

const handleFormRegencyChange = async () => {
    form.district_id = null;
    await loadDistricts(form.regency_id);
};

const submit = () => {
    if ((editingRecord.value && !props.can.update) || (!editingRecord.value && !props.can.create)) return;

    if (editingRecord.value) {
        form.put(`${resourceUrl(props.currentResource)}/${editingRecord.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (showForm.value = false),
        });
        return;
    }

    form.post(resourceUrl(props.currentResource), {
        preserveScroll: true,
        onSuccess: () => (showForm.value = false),
    });
};

const deleteRecord = (record) => {
    if (!props.can.delete) return;

    confirm.require({
        header: `Hapus ${currentMeta.value?.singular ?? "data"}?`,
        message: `Data "${record.name}" akan dihapus. Data turunan juga dapat ikut terhapus jika ada relasi cascade.`,
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        acceptLabel: "Hapus",
        rejectLabel: "Batal",
        accept: () => router.delete(`${resourceUrl(props.currentResource)}/${record.id}`, { preserveScroll: true }),
    });
};

const parentText = (record) => {
    if (props.currentResource === "regencies") return record.province?.name ?? "-";
    if (props.currentResource === "districts") {
        return [record.regency?.name, record.regency?.province?.name].filter(Boolean).join(" / ") || "-";
    }
    if (props.currentResource === "villages") {
        return [record.district?.name, record.district?.regency?.name, record.district?.regency?.province?.name]
            .filter(Boolean)
            .join(" / ") || "-";
    }
    return "-";
};

const childCount = (record) => {
    if (props.currentResource === "provinces") return record.regencies_count ?? 0;
    if (props.currentResource === "regencies") return record.districts_count ?? 0;
    if (props.currentResource === "districts") return record.villages_count ?? 0;
    return null;
};

const formatNumber = (value) => new Intl.NumberFormat("id-ID").format(Number(value ?? 0));
</script>

<template>
    <AdminLayout title="Data Lokasi - Admin">
        <Head :title="`${title} - Admin`" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <UiSurface padding="none" class="overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-balance text-sm font-bold text-slate-900">Data Lokasi</p>
                        <p class="mt-1 text-pretty text-xs text-slate-500">Kelola hirarki wilayah Indonesia.</p>
                    </div>

                    <nav class="p-2">
                        <Link
                            v-for="resource in resources"
                            :key="resource.slug"
                            :href="resourceUrl(resource.slug)"
                            class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold"
                            :class="currentResource === resource.slug ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <i :class="resource.icon" class="shrink-0 text-xs" />
                                <span class="truncate">{{ resource.label }}</span>
                            </span>
                            <span
                                class="ui-tabular shrink-0 rounded-full px-2 py-0.5 text-[11px]"
                                :class="currentResource === resource.slug ? 'bg-white/10 text-white' : 'bg-slate-100 text-slate-500'"
                            >
                                {{ formatNumber(stats?.[resource.slug]) }}
                            </span>
                        </Link>
                    </nav>
                </UiSurface>
            </aside>

            <section class="min-w-0 lg:col-span-9">
                <UiSurface v-if="!currentResource" class="min-h-[520px]" padding="lg">
                    <div class="grid h-full place-items-center py-16 text-center">
                        <div class="max-w-md">
                            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="pi pi-map text-2xl" />
                            </div>
                            <h1 class="mt-5 text-balance text-2xl font-black text-slate-900">Pilih data lokasi</h1>
                            <p class="mt-2 text-pretty text-sm text-slate-500">
                                Mulai dari provinsi, lalu lanjutkan ke kabupaten / kota, kecamatan, dan desa / kelurahan.
                            </p>
                            <div class="mt-6 grid grid-cols-2 gap-2 text-left sm:grid-cols-4">
                                <div
                                    v-for="resource in resources"
                                    :key="resource.slug"
                                    class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                                >
                                    <p class="truncate text-xs font-semibold text-slate-500">{{ resource.label }}</p>
                                    <p class="ui-tabular mt-1 text-lg font-black text-slate-900">{{ formatNumber(stats?.[resource.slug]) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </UiSurface>

                <template v-else>
                    <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h1 class="text-balance text-2xl font-black text-slate-900">{{ currentMeta?.label }}</h1>
                            <p class="mt-1 text-pretty text-sm text-slate-500">
                                {{ formatNumber(selectedStat) }} data tersimpan. Kode wilayah mengikuti format BPS.
                            </p>
                        </div>

                        <Button v-if="props.can.create" label="Tambah Data" icon="pi pi-plus" @click="openCreate" />
                    </div>

                    <UiSurface padding="none" class="overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                                <span class="relative xl:col-span-2">
                                    <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                                    <InputText
                                        v-model="filterState.search"
                                        class="w-full pl-9"
                                        placeholder="Cari kode atau nama"
                                    />
                                </span>

                                <Dropdown
                                    v-if="['regencies', 'districts', 'villages'].includes(currentResource)"
                                    v-model="filterState.province_id"
                                    :options="options.provinces"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="Provinsi"
                                    filter
                                    show-clear
                                    class="w-full"
                                    @change="handleProvinceFilterChange"
                                />

                                <Dropdown
                                    v-if="['districts', 'villages'].includes(currentResource)"
                                    v-model="filterState.regency_id"
                                    :options="regencyOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="Kabupaten / Kota"
                                    filter
                                    show-clear
                                    class="w-full"
                                    :disabled="!filterState.province_id"
                                    :loading="loadingRegencies"
                                    @change="handleRegencyFilterChange"
                                />

                                <Dropdown
                                    v-if="currentResource === 'villages'"
                                    v-model="filterState.district_id"
                                    :options="districtOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="Kecamatan"
                                    filter
                                    show-clear
                                    class="w-full"
                                    :disabled="!filterState.regency_id"
                                    :loading="loadingDistricts"
                                    @change="applyFilters"
                                />

                                <Dropdown
                                    v-model="filterState.per_page"
                                    :options="[10, 20, 50, 100]"
                                    placeholder="Per halaman"
                                    class="w-full"
                                    @change="applyFilters"
                                />
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <Button
                                    label="Reset Filter"
                                    icon="pi pi-filter-slash"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    @click="resetFilters"
                                />
                                <Tag v-if="filterState.search" :value="`Cari: ${filterState.search}`" severity="info" />
                                <Tag v-if="filterState.province_id" :value="`Provinsi: ${filterState.province_id}`" severity="secondary" />
                                <Tag v-if="filterState.regency_id" :value="`Kab/Kota: ${filterState.regency_id}`" severity="secondary" />
                                <Tag v-if="filterState.district_id" :value="`Kecamatan: ${filterState.district_id}`" severity="secondary" />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <thead class="border-b border-slate-100 bg-white text-[11px] font-bold uppercase text-slate-400">
                                    <tr>
                                        <th class="px-5 py-4">Kode</th>
                                        <th class="px-5 py-4">Nama</th>
                                        <th v-if="hasParentColumn" class="px-5 py-4">Induk</th>
                                        <th v-if="hasChildCount" class="px-5 py-4">{{ currentMeta?.children_label }}</th>
                                        <th v-if="props.can.update || props.can.delete" class="px-5 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="record in records.data" :key="record.id" class="hover:bg-slate-50">
                                        <td class="ui-tabular px-5 py-4 font-semibold text-slate-600">{{ record.id }}</td>
                                        <td class="px-5 py-4">
                                            <p class="max-w-md truncate font-bold text-slate-900">{{ record.name }}</p>
                                        </td>
                                        <td v-if="hasParentColumn" class="px-5 py-4">
                                            <p class="max-w-sm truncate text-xs font-medium text-slate-600">{{ parentText(record) }}</p>
                                        </td>
                                        <td v-if="hasChildCount" class="px-5 py-4">
                                            <span class="ui-tabular rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-600">
                                                {{ formatNumber(childCount(record)) }}
                                            </span>
                                        </td>
                                        <td v-if="props.can.update || props.can.delete" class="px-5 py-4">
                                            <div class="flex justify-end gap-1">
                                                <Button v-if="props.can.update" icon="pi pi-pencil" text rounded severity="secondary" aria-label="Edit" @click="openEdit(record)" />
                                                <Button v-if="props.can.delete" icon="pi pi-trash" text rounded severity="danger" aria-label="Hapus" @click="deleteRecord(record)" />
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="records.data.length === 0">
                                        <td :colspan="2 + (hasParentColumn ? 1 : 0) + (hasChildCount ? 1 : 0) + 1" class="px-5 py-8">
                                            <UiEmptyState
                                                title="Data tidak ditemukan"
                                                description="Ubah filter pencarian atau tambah data lokasi baru."
                                                icon="pi pi-map-marker"
                                            >
                                                <template #actions>
                                                    <Button v-if="props.can.create" label="Tambah Data" icon="pi pi-plus" size="small" @click="openCreate" />
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
                                <template v-for="(link, index) in records.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                                        :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-300"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </UiSurface>
                </template>
            </section>
        </div>

        <Dialog
            v-model:visible="showForm"
            modal
            :draggable="false"
            :header="editingRecord ? `Edit ${currentMeta?.singular}` : `Tambah ${currentMeta?.singular}`"
            style="width: min(560px, 100%)"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <UiField v-if="currentResource === 'provinces' && !editingRecord" id="geo_id" :label="currentMeta?.id_label" required :error="form.errors.id" :help="currentMeta?.id_help">
                    <InputText id="geo_id" v-model="form.id" class="w-full" maxlength="2" placeholder="Contoh: 32" />
                </UiField>

                <UiField v-if="currentResource !== 'provinces'" id="geo_province" label="Provinsi" required :error="form.errors.province_id">
                    <Dropdown
                        input-id="geo_province"
                        v-model="form.province_id"
                        :options="options.provinces"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih provinsi"
                        filter
                        class="w-full"
                        :disabled="Boolean(editingRecord)"
                        @change="handleFormProvinceChange"
                    />
                </UiField>

                <UiField v-if="['districts', 'villages'].includes(currentResource)" id="geo_regency" label="Kabupaten / Kota" required :error="form.errors.regency_id">
                    <Dropdown
                        input-id="geo_regency"
                        v-model="form.regency_id"
                        :options="regencyOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih kabupaten / kota"
                        filter
                        class="w-full"
                        :disabled="Boolean(editingRecord) || !form.province_id"
                        :loading="loadingRegencies"
                        @change="handleFormRegencyChange"
                    />
                </UiField>

                <UiField v-if="currentResource === 'villages'" id="geo_district" label="Kecamatan" required :error="form.errors.district_id">
                    <Dropdown
                        input-id="geo_district"
                        v-model="form.district_id"
                        :options="districtOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih kecamatan"
                        filter
                        class="w-full"
                        :disabled="Boolean(editingRecord) || !form.regency_id"
                        :loading="loadingDistricts"
                    />
                </UiField>

                <UiField id="geo_name" label="Nama" required :error="form.errors.name" help="Nama akan disimpan dengan huruf kapital.">
                    <InputText id="geo_name" v-model="form.name" class="w-full" placeholder="Tulis nama wilayah" />
                </UiField>

                <p v-if="editingRecord && currentResource !== 'provinces'" class="text-pretty text-xs font-medium text-slate-500">
                    Induk wilayah tidak diubah saat edit karena kode wilayah dibuat dari induk saat data ditambahkan.
                </p>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <Button label="Batal" severity="secondary" outlined :disabled="form.processing" @click="showForm = false" />
                    <Button
                        :label="editingRecord ? 'Simpan Perubahan' : 'Tambah Data'"
                        icon="pi pi-save"
                        type="submit"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
