<script setup>
import { ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Tag from "primevue/tag";
import debounce from "lodash/debounce";

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: "" }) },
});

const search = ref(props.filters.search);

watch(
    search,
    debounce((value) => {
        router.get(
            "/admin/activity-logs",
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300)
);

const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    });
};
</script>

<template>
    <AdminLayout title="Activity Logs - Admin">
        <Head title="Activity Logs - Admin" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-balance text-2xl font-black text-slate-900">Activity Logs</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Rekam jejak seluruh aktivitas pengguna dan perubahan data di dalam sistem.
                </p>
            </div>
            <div class="w-full md:w-72">
                <span class="p-input-icon-left w-full relative">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <InputText
                        v-model="search"
                        placeholder="Cari log nama, event..."
                        class="w-full pl-10"
                    />
                </span>
            </div>
        </div>

        <UiSurface padding="none" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-700 border-b border-slate-200">
                        <tr>
                            <th scope="col" class="px-6 py-3">Waktu</th>
                            <th scope="col" class="px-6 py-3">Causer (User)</th>
                            <th scope="col" class="px-6 py-3">Subject / Event</th>
                            <th scope="col" class="px-6 py-3">Description</th>
                            <th scope="col" class="px-6 py-3 w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-500">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="log.causer">
                                    <p class="font-bold text-slate-900">{{ log.causer.name }}</p>
                                    <p class="text-xs text-slate-500">{{ log.causer.email }}</p>
                                </div>
                                <span v-else class="text-slate-400 italic">Sistem</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Tag 
                                        :value="log.event || 'Unknown'" 
                                        :severity="['created', 'updated', 'deleted'].includes(log.event) ? (log.event === 'deleted' ? 'danger' : (log.event === 'created' ? 'success' : 'info')) : 'secondary'"
                                        class="uppercase text-[10px]"
                                    />
                                    <span class="text-xs font-semibold text-slate-700">
                                        {{ log.log_name || log.subject_type }}
                                    </span>
                                </div>
                                <p v-if="log.subject_id" class="mt-1 text-xs text-slate-400">
                                    ID: {{ log.subject_id }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ log.description }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <Button
                                    icon="pi pi-eye"
                                    severity="secondary"
                                    text
                                    rounded
                                    v-tooltip="'Lihat Detail'"
                                    @click="router.get(`/admin/activity-logs/${log.id}`)"
                                />
                            </td>
                        </tr>
                        <tr v-if="!logs.data.length">
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                Tidak ada log aktivitas yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.links && logs.links.length > 3" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-6 py-4">
                <span class="text-sm text-slate-500">
                    Menampilkan {{ logs.from }} s/d {{ logs.to }} dari {{ logs.total }} log
                </span>
                <div class="flex gap-1">
                    <template v-for="(link, index) in logs.links" :key="index">
                        <Button
                            v-if="link.url"
                            @click="router.get(link.url)"
                            :label="link.label.replace('&laquo; Previous', 'Prev').replace('Next &raquo;', 'Next')"
                            :severity="link.active ? 'primary' : 'secondary'"
                            :outlined="!link.active"
                            class="!p-2 text-xs"
                        />
                        <span v-else class="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-400" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </UiSurface>
    </AdminLayout>
</template>
