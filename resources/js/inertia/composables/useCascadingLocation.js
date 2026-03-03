import { onMounted, reactive, ref, watch } from "vue";

const defaultEndpoints = {
    regencies: "/home/lookups/regencies",
    districts: "/home/lookups/districts",
    villages: "/home/lookups/villages",
};

const defaultKeys = {
    province: "province_id",
    regency: "regency_id",
    district: "district_id",
    village: "village_id",
};

const buildLookupUrl = (endpoint, key, value) => {
    const params = new URLSearchParams({ [key]: String(value) });
    return `${endpoint}?${params.toString()}`;
};

export const useCascadingLocation = (source, config = {}) => {
    const keys = { ...defaultKeys, ...(config.keys ?? {}) };
    const endpoints = { ...defaultEndpoints, ...(config.endpoints ?? {}) };
    const initialOptions = config.initialOptions ?? {};

    const regencyOptions = ref([...(initialOptions.regencies ?? [])]);
    const districtOptions = ref([...(initialOptions.districts ?? [])]);
    const villageOptions = ref([...(initialOptions.villages ?? [])]);

    const locationLoading = reactive({
        regencies: false,
        districts: false,
        villages: false,
    });

    let regenciesRequestId = 0;
    let districtsRequestId = 0;
    let villagesRequestId = 0;

    const loadRegencies = async () => {
        const requestId = ++regenciesRequestId;
        regencyOptions.value = [];
        districtOptions.value = [];
        villageOptions.value = [];

        const provinceId = source[keys.province];
        if (!provinceId) return;

        locationLoading.regencies = true;
        try {
            const response = await fetch(buildLookupUrl(endpoints.regencies, keys.province, provinceId));
            const payload = response.ok ? await response.json() : [];

            if (requestId !== regenciesRequestId) return;
            regencyOptions.value = Array.isArray(payload) ? payload : [];
        } catch (error) {
            if (requestId !== regenciesRequestId) return;
            regencyOptions.value = [];
            console.error("Failed to load regencies", error);
        } finally {
            if (requestId === regenciesRequestId) {
                locationLoading.regencies = false;
            }
        }
    };

    const loadDistricts = async () => {
        const requestId = ++districtsRequestId;
        districtOptions.value = [];
        villageOptions.value = [];

        const regencyId = source[keys.regency];
        if (!regencyId) return;

        locationLoading.districts = true;
        try {
            const response = await fetch(buildLookupUrl(endpoints.districts, keys.regency, regencyId));
            const payload = response.ok ? await response.json() : [];

            if (requestId !== districtsRequestId) return;
            districtOptions.value = Array.isArray(payload) ? payload : [];
        } catch (error) {
            if (requestId !== districtsRequestId) return;
            districtOptions.value = [];
            console.error("Failed to load districts", error);
        } finally {
            if (requestId === districtsRequestId) {
                locationLoading.districts = false;
            }
        }
    };

    const loadVillages = async () => {
        const requestId = ++villagesRequestId;
        villageOptions.value = [];

        const districtId = source[keys.district];
        if (!districtId) return;

        locationLoading.villages = true;
        try {
            const response = await fetch(buildLookupUrl(endpoints.villages, keys.district, districtId));
            const payload = response.ok ? await response.json() : [];

            if (requestId !== villagesRequestId) return;
            villageOptions.value = Array.isArray(payload) ? payload : [];
        } catch (error) {
            if (requestId !== villagesRequestId) return;
            villageOptions.value = [];
            console.error("Failed to load villages", error);
        } finally {
            if (requestId === villagesRequestId) {
                locationLoading.villages = false;
            }
        }
    };

    watch(() => source[keys.province], (value, oldValue) => {
        if (value === oldValue) return;
        source[keys.regency] = null;
        source[keys.district] = null;
        source[keys.village] = null;
        loadRegencies();
    });

    watch(() => source[keys.regency], (value, oldValue) => {
        if (value === oldValue) return;
        source[keys.district] = null;
        source[keys.village] = null;
        loadDistricts();
    });

    watch(() => source[keys.district], (value, oldValue) => {
        if (value === oldValue) return;
        source[keys.village] = null;
        loadVillages();
    });

    const preloadLocationOptions = async ({ respectInitialOptions = false } = {}) => {
        if (source[keys.province] && (!respectInitialOptions || regencyOptions.value.length === 0)) {
            await loadRegencies();
        }

        if (source[keys.regency] && (!respectInitialOptions || districtOptions.value.length === 0)) {
            await loadDistricts();
        }

        if (source[keys.district] && (!respectInitialOptions || villageOptions.value.length === 0)) {
            await loadVillages();
        }
    };

    if (config.preloadOnMounted) {
        onMounted(() => preloadLocationOptions({
            respectInitialOptions: Boolean(config.respectInitialOptionsOnPreload),
        }));
    }

    return {
        regencyOptions,
        districtOptions,
        villageOptions,
        locationLoading,
        loadRegencies,
        loadDistricts,
        loadVillages,
        preloadLocationOptions,
    };
};
