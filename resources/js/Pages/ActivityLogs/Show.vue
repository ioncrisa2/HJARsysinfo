<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import UiSurface from "../../components/ui/UiSurface.vue";
import Button from "primevue/button";
import Tag from "primevue/tag";

const props = defineProps({
    log: { type: Object, required: true },
});

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

const hasProperties = computed(() => {
    return props.log.properties && Object.keys(props.log.properties).length > 0;
});

// A computed property to format the JSON nicely if properties exist
const formattedProperties = computed(() => {
    if (!hasProperties.value) return "{}";
    return JSON.stringify(props.log.properties, null, 4);
});
</script>

<template>
    <AppLayout title="Detail Activity Log">
        <Head title="Detail Activity Log" />

        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-1">
                <Link
                    href="/app/activity-logs"
                    class="inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors"
                >
                    <i class="pi pi-arrow-left text-[10px]" /> Kembali ke Daftar Log
                </Link>
                <h1 class="text-balance text-2xl font-black text-slate-900 mt-2">Detail Activity Log</h1>
                <p class="mt-1 text-pretty text-sm text-slate-500">
                    Menampilkan rincian penuh perubahan data atau aktivitas pengguna.
                </p>
            </div>
            <div>
                <Tag 
                    :value="log.event || 'Unknown'" 
                    :severity="['created', 'updated', 'deleted'].includes(log.event) ? (log.event === 'deleted' ? 'danger' : (log.event === 'created' ? 'success' : 'info')) : 'secondary'"
                    class="uppercase font-bold tracking-wider px-3 py-1"
                />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Informasi Utama -->
            <div class="lg:col-span-1 space-y-6">
                <UiSurface>
                    <h2 class="text-base font-bold text-slate-900 mb-4">Informasi Log</h2>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Waktu Terjadi</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(log.created_at) }}</dd>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <dt class="text-xs font-semibold text-slate-500">Log Name / Module</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ log.log_name || '-' }}</dd>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <dt class="text-xs font-semibold text-slate-500">Subject Type (Model)</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900 break-words">{{ log.subject_type || '-' }}</dd>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <dt class="text-xs font-semibold text-slate-500">Subject ID</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ log.subject_id || '-' }}</dd>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <dt class="text-xs font-semibold text-slate-500">Description</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ log.description || '-' }}</dd>
                        </div>
                    </dl>
                </UiSurface>

                <UiSurface>
                    <h2 class="text-base font-bold text-slate-900 mb-4">Informasi Pengguna (Causer)</h2>
                    <div v-if="log.causer" class="flex items-start gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-bold">
                            {{ log.causer.name ? log.causer.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ log.causer.name }}</p>
                            <p class="text-xs text-slate-500">{{ log.causer.email }}</p>
                            <p class="mt-2 text-xs text-slate-400">ID Pengguna: {{ log.causer_id }}</p>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-4">
                        <i class="pi pi-desktop text-2xl text-slate-300 mb-2"></i>
                        <p class="text-sm font-medium text-slate-500">Aksi Sistem / Otomatis</p>
                    </div>
                </UiSurface>
            </div>

            <!-- Properties JSON View -->
            <div class="lg:col-span-2">
                <UiSurface class="h-full flex flex-col">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Properties (Detail Perubahan)</h2>
                        <p class="mt-1 text-xs text-slate-500">Menampilkan nilai atribut sebelum (old) dan sesudah (attributes) perubahan.</p>
                    </div>
                    <div class="flex-1 bg-slate-900 rounded-lg overflow-hidden flex flex-col min-h-[300px]">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between">
                            <span class="text-xs font-mono text-slate-400">JSON Payload</span>
                            <span class="text-[10px] bg-slate-700 text-slate-300 px-2 py-0.5 rounded uppercase font-bold">{{ log.event }}</span>
                        </div>
                        <div class="p-4 overflow-x-auto flex-1 text-sm font-mono text-slate-300">
                            <template v-if="hasProperties">
                                <pre><code>{{ formattedProperties }}</code></pre>
                            </template>
                            <template v-else>
                                <div class="h-full flex items-center justify-center text-slate-500 italic">
                                    Tidak ada properties data yang direkam.
                                </div>
                            </template>
                        </div>
                    </div>
                </UiSurface>
            </div>
        </div>
    </AppLayout>
</template>
