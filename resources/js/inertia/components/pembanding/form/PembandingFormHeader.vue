<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import UiSurface from "../../ui/UiSurface.vue";

const props = defineProps({
    mode: { type: String, default: "create" },
    recordId: { type: [String, Number], default: null },
    address: { type: String, default: "" },
    processing: { type: Boolean, default: false },
    isDirty: { type: Boolean, default: false },
    flashSuccess: { type: String, default: null },
    activeTab: { type: String, default: "umum" },
});

const emit = defineEmits(["submit", "submit-and-create-another"]);

const isCreate = props.mode === "create";
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="flashSuccess"
            class="rounded-[var(--radius-md)] border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
            role="status"
        >
            {{ flashSuccess }}
        </div>

        <UiSurface padding="none" class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                    <Link href="/home/pembanding" class="inline-flex items-center gap-2 font-semibold text-slate-700">
                        <i class="pi pi-database text-[11px] text-amber-600" aria-hidden="true" />
                        Bank Data
                    </Link>
                    <i class="pi pi-chevron-right text-[9px] text-slate-300" aria-hidden="true" />
                    <template v-if="!isCreate">
                        <Link :href="`/home/pembanding/${recordId}`" class="font-semibold text-slate-700">
                            Detail #{{ recordId }}
                        </Link>
                        <i class="pi pi-chevron-right text-[9px] text-slate-300" aria-hidden="true" />
                    </template>
                    <span class="font-medium text-slate-600">{{ isCreate ? "Tambah" : "Edit" }}</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="!isCreate && isDirty"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600"
                    >
                        <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true" />
                        Belum disimpan
                    </span>

                    <template v-if="isCreate">
                        <Button
                            label="Simpan"
                            icon="pi pi-save"
                            size="small"
                            :loading="processing"
                            @click="emit('submit')"
                        />
                        <Button
                            label="Simpan & Buat Lagi"
                            icon="pi pi-plus"
                            size="small"
                            severity="secondary"
                            outlined
                            :loading="processing"
                            @click="emit('submit-and-create-another')"
                        />
                    </template>
                    <template v-else>
                        <Button
                            label="Simpan"
                            icon="pi pi-save"
                            size="small"
                            :loading="processing"
                            :disabled="!isDirty"
                            @click="emit('submit')"
                        />
                    </template>
                </div>
            </div>

            <div class="px-4 py-4">
                <h1 class="text-balance text-lg font-semibold text-slate-900 sm:text-xl">
                    {{ isCreate ? "Tambah Data Pembanding" : "Edit Data Pembanding" }}
                </h1>
                <p v-if="!isCreate && address" class="mt-1 line-clamp-1 text-pretty text-sm text-slate-500">
                    {{ address }}
                </p>
                <p v-else-if="isCreate" class="mt-1 text-pretty text-sm text-slate-500">
                    Lengkapi data inti, lokasi, properti, lalu catatan (opsional).
                </p>
            </div>
        </UiSurface>
    </div>
</template>
