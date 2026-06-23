<script setup>
import { ref, computed, watch, onMounted } from "vue";
import Message from "primevue/message";
import { apiRequest } from "../../utils/apiRequest";
import { useDebouncedWatch } from "../../composables/useDebouncedWatch";

import LocationBreadcrumb from "./location/LocationBreadcrumb.vue";
import LocationDataTable from "./location/LocationDataTable.vue";
import LocationFormDialog from "./location/LocationFormDialog.vue";
import LocationDeleteDialog from "./location/LocationDeleteDialog.vue";

const emit = defineEmits(["success", "error"]);

// ─── State ───────────────────────────────────────────────────────────────────
const loading = ref(false);
const items = ref([]);
const searchQuery = ref("");
const error = ref(null);

// Breadcrumb state
// Each item in path: { level: 'province'|'regency'|'district'|'village', id: string, name: string }
const path = ref([]);

const currentLevel = computed(() => {
    if (path.value.length === 0) return "province";
    if (path.value.length === 1) return "regency";
    if (path.value.length === 2) return "district";
    return "village";
});

const currentParent = computed(() => {
    if (path.value.length === 0) return null;
    return path.value[path.value.length - 1];
});

const levelNames = {
    province: "Provinsi",
    regency: "Kabupaten/Kota",
    district: "Kecamatan",
    village: "Desa/Kelurahan"
};

// ─── Data Fetching ───────────────────────────────────────────────────────────
const loadData = async () => {
    loading.value = true;
    error.value = null;
    try {
        let endpoint = "";
        const sq = searchQuery.value ? `&q=${encodeURIComponent(searchQuery.value)}` : "";
        
        if (currentLevel.value === "province") {
            endpoint = `/home/master-data/locations/provinces`;
        } else if (currentLevel.value === "regency") {
            endpoint = `/home/master-data/locations/regencies?province_id=${currentParent.value.id}${sq}`;
        } else if (currentLevel.value === "district") {
            endpoint = `/home/master-data/locations/districts?regency_id=${currentParent.value.id}${sq}`;
        } else if (currentLevel.value === "village") {
            endpoint = `/home/master-data/locations/villages?district_id=${currentParent.value.id}${sq}`;
        }

        const data = await apiRequest(endpoint);
        
        // Client-side filtering for provinces since backend doesn't support ?q= for provinces
        if (currentLevel.value === "province" && searchQuery.value) {
            const lowerQ = searchQuery.value.toLowerCase();
            items.value = data.filter(d => 
                (d.name || "").toLowerCase().includes(lowerQ) || 
                (d.id || "").toLowerCase().includes(lowerQ)
            );
        } else {
            items.value = data;
        }
    } catch (err) {
        error.value = err?.message || "Gagal memuat data lokasi";
        emit("error", error.value);
    } finally {
        loading.value = false;
    }
};

useDebouncedWatch(searchQuery, () => {
    loadData();
}, { delay: 300 });

onMounted(() => {
    loadData();
});

// ─── Navigation ──────────────────────────────────────────────────────────────
const drillDown = (row) => {
    if (currentLevel.value === "village") return;
    path.value.push({
        level: currentLevel.value,
        id: row.id,
        name: row.name
    });
    searchQuery.value = "";
    loadData();
};

const navigateUp = (index) => {
    if (index === -1) {
        path.value = [];
    } else {
        path.value = path.value.slice(0, index + 1);
    }
    searchQuery.value = "";
    loadData();
};

// ─── CRUD Operations ─────────────────────────────────────────────────────────
const showDialog = ref(false);
const dialogMode = ref("add"); // "add" | "edit"
const form = ref({ id: "", name: "", original_id: null });
const submitting = ref(false);

const openAddDialog = () => {
    dialogMode.value = "add";
    form.value = { id: "", name: "", original_id: null };
    showDialog.value = true;
};

const openEditDialog = (row) => {
    dialogMode.value = "edit";
    form.value = { id: row.id, name: row.name, original_id: row.id };
    showDialog.value = true;
};

const saveItem = async () => {
    submitting.value = true;
    error.value = null;
    try {
        let endpoint = "";
        let method = dialogMode.value === "add" ? "POST" : "PUT";
        let body = { name: form.value.name };

        if (currentLevel.value === "province") {
            endpoint = dialogMode.value === "add" 
                ? `/home/master-data/locations/provinces` 
                : `/home/master-data/locations/provinces/${form.value.original_id}`;
            if (dialogMode.value === "add") body.id = form.value.id;
        } else if (currentLevel.value === "regency") {
            endpoint = dialogMode.value === "add" 
                ? `/home/master-data/locations/regencies` 
                : `/home/master-data/locations/regencies/${form.value.original_id}`;
            body.province_id = currentParent.value.id;
        } else if (currentLevel.value === "district") {
            endpoint = dialogMode.value === "add" 
                ? `/home/master-data/locations/districts` 
                : `/home/master-data/locations/districts/${form.value.original_id}`;
            body.regency_id = currentParent.value.id;
        } else if (currentLevel.value === "village") {
            endpoint = dialogMode.value === "add" 
                ? `/home/master-data/locations/villages` 
                : `/home/master-data/locations/villages/${form.value.original_id}`;
            body.district_id = currentParent.value.id;
        }

        await apiRequest(endpoint, { method, body });
        
        emit("success", `${levelNames[currentLevel.value]} berhasil disimpan`);
        showDialog.value = false;
        loadData();
    } catch (err) {
        emit("error", err?.message || "Gagal menyimpan data");
    } finally {
        submitting.value = false;
    }
};

const showDeleteDialog = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (row) => {
    itemToDelete.value = row;
    showDeleteDialog.value = true;
};

const deleteItem = async () => {
    if (!itemToDelete.value) return;
    submitting.value = true;
    try {
        let endpoint = "";
        if (currentLevel.value === "province") endpoint = `/home/master-data/locations/provinces/${itemToDelete.value.id}`;
        else if (currentLevel.value === "regency") endpoint = `/home/master-data/locations/regencies/${itemToDelete.value.id}`;
        else if (currentLevel.value === "district") endpoint = `/home/master-data/locations/districts/${itemToDelete.value.id}`;
        else if (currentLevel.value === "village") endpoint = `/home/master-data/locations/villages/${itemToDelete.value.id}`;

        await apiRequest(endpoint, { method: "DELETE" });
        
        emit("success", `${levelNames[currentLevel.value]} berhasil dihapus`);
        showDeleteDialog.value = false;
        loadData();
    } catch (err) {
        emit("error", err?.message || "Gagal menghapus data");
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col gap-4 h-full">
        <LocationBreadcrumb 
            v-model:searchQuery="searchQuery"
            :path="path"
            :currentLevel="currentLevel"
            :levelNames="levelNames"
            @navigate-up="navigateUp"
            @add="openAddDialog"
        />

        <LocationDataTable 
            :items="items"
            :loading="loading"
            :currentLevel="currentLevel"
            @edit="openEditDialog"
            @delete="confirmDelete"
            @drill-down="drillDown"
        />

        <LocationFormDialog 
            v-model:visible="showDialog"
            :mode="dialogMode"
            :currentLevel="currentLevel"
            :levelNames="levelNames"
            :form="form"
            :submitting="submitting"
            @save="saveItem"
        />

        <LocationDeleteDialog 
            v-model:visible="showDeleteDialog"
            :item="itemToDelete"
            :currentLevel="currentLevel"
            :levelNames="levelNames"
            :submitting="submitting"
            @confirm="deleteItem"
        />
        
        <div v-if="error" class="px-4 pb-4">
            <Message severity="error" :closable="false">{{ error }}</Message>
        </div>
    </div>
</template>
