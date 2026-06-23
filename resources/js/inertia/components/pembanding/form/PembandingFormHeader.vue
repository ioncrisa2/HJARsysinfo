<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import Button from "primevue/button";
import UiSurface from "../../ui/UiSurface.vue";
import { TAB_ORDER } from "../../../config/pembandingFormRequiredFields";

const props = defineProps({
    mode: { type: String, default: "create" },
    basePath: { type: String, required: true },
    recordId: { type: [String, Number], default: null },
    address: { type: String, default: "" },
    processing: { type: Boolean, default: false },
    isDirty: { type: Boolean, default: false },
    flashSuccess: { type: String, default: null },
    activeTab: { type: String, default: "umum" },
});

const emit = defineEmits(["submit", "submit-and-create-another"]);

const isCreate = computed(() => props.mode === "create");
const detailPath = computed(() => !isCreate.value && props.recordId ? `${props.basePath}/${props.recordId}` : props.basePath);
const progressWidth = computed(() => {
    const index = TAB_ORDER.indexOf(props.activeTab);
    const safeIndex = index === -1 ? 0 : index;

    return `${((safeIndex + 1) / TAB_ORDER.length) * 100}%`;
});
</script>

<template>
    <div class="space-y-4">
        <!-- Breadcrumbs & Status -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <nav class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-slate-400">
                <Link :href="basePath" class="hover:text-slate-900 transition-colors">Bank Data</Link>
                <i class="pi pi-chevron-right text-[8px]" />
                <Link
                    v-if="!isCreate"
                    :href="detailPath"
                    class="text-slate-400 transition-colors hover:text-slate-900"
                >
                    #{{ recordId }}
                </Link>
                <i v-if="!isCreate" class="pi pi-chevron-right text-[8px]" />
                <span class="text-amber-600">{{ isCreate ? "Tambah Data" : "Edit Data" }}</span>
            </nav>

            <div v-if="!isCreate" class="flex items-center gap-2">
                <div 
                    class="h-2 w-2 rounded-full animate-pulse"
                    :class="isDirty ? 'bg-amber-500' : 'bg-green-500'"
                />
                <span class="text-[10px] font-bold uppercase tracking-tighter text-slate-500">
                    {{ isDirty ? 'Ada perubahan yang belum disimpan' : 'Semua perubahan tersimpan' }}
                </span>
            </div>
        </div>

        <!-- Main Header Surface -->
        <UiSurface padding="none" class="overflow-hidden border-slate-200 shadow-sm rounded-2xl">
            <div class="p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ isCreate ? "Tambah Data Pembanding" : "Update Data Pembanding" }}
                    </h1>
                    <p v-if="!isCreate && address" class="text-sm text-slate-500 font-medium">
                        <i class="pi pi-map-marker text-[10px] mr-1" /> {{ address }}
                    </p>
                    <p v-else-if="isCreate" class="text-sm text-slate-500 font-medium">
                        Silahkan lengkapi seluruh field wajib di setiap tab.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <template v-if="isCreate">
                        <Button
                            label="Simpan & Buat Lagi"
                            icon="pi pi-plus"
                            severity="secondary"
                            outlined
                            class="rounded-xl px-5 font-bold text-xs"
                            :loading="processing"
                            :disabled="processing"
                            @click="emit('submit-and-create-another')"
                        />
                        <Button
                            label="Simpan Data"
                            icon="pi pi-save"
                            severity="primary"
                            class="rounded-xl px-8 font-bold text-xs shadow-lg shadow-slate-200"
                            :loading="processing"
                            :disabled="processing"
                            @click="emit('submit')"
                        />
                    </template>
                    <template v-else>
                        <Link :href="basePath">
                            <Button
                                label="Batalkan"
                                severity="secondary"
                                text
                                class="rounded-xl px-4 font-bold text-xs"
                            />
                        </Link>
                        <Button
                            label="Update Data"
                            icon="pi pi-save"
                            severity="primary"
                            class="rounded-xl px-10 font-bold text-xs shadow-lg shadow-slate-200"
                            :loading="processing"
                            :disabled="processing || !isDirty"
                            @click="emit('submit')"
                        />
                    </template>
                </div>
            </div>
            
            <!-- Tab Progress Placeholder / Subtle border -->
            <div class="h-1 bg-slate-100 w-full">
                <div 
                    class="h-full bg-amber-500 transition-all duration-500" 
                    :style="{ width: progressWidth }"
                />
            </div>
        </UiSurface>

        <!-- Success Message -->
        <div
            v-if="flashSuccess"
            class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-800 flex items-center gap-3 animate-bounce"
            role="status"
        >
            <i class="pi pi-check-circle" />
            {{ flashSuccess }}
        </div>
    </div>
</template>
