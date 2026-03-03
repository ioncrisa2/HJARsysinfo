<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "primevue/button";

const props = defineProps({
    mode:         { type: String,          default: "create" },
    recordId:     { type: [String, Number], default: null },
    address:      { type: String,          default: "" },
    processing:   { type: Boolean,         default: false },
    isDirty:      { type: Boolean,         default: false },
    flashSuccess: { type: String,          default: null },
    activeTab:    { type: String,          default: "umum" },
});

const emit = defineEmits(["submit", "submit-and-create-another"]);

const isCreate = props.mode === "create";

const steps = [
    { key: "umum",     label: "Umum",     icon: "pi-info-circle" },
    { key: "lokasi",   label: "Lokasi",   icon: "pi-map-marker"  },
    { key: "properti", label: "Properti", icon: "pi-building"    },
    { key: "catatan",  label: "Catatan",  icon: "pi-file-edit"   },
];

const activeIndex = () => steps.findIndex((s) => s.key === props.activeTab);
</script>

<template>
    <div class="space-y-3">

        <!-- Flash success -->
        <Transition name="flash">
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            >
                <i class="pi pi-check-circle shrink-0 text-emerald-500" />
                {{ flashSuccess }}
            </div>
        </Transition>

        <!-- Header card -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <!-- Top bar: breadcrumb + actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-3">

                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 text-xs text-slate-400">
                    <Link
                        href="/home/pembanding"
                        class="inline-flex items-center gap-1 font-medium transition-colors hover:text-amber-600"
                    >
                        <i class="pi pi-database text-[10px]" />
                        Bank Data
                    </Link>
                    <i class="pi pi-chevron-right text-[9px] text-slate-300" />
                    <template v-if="!isCreate">
                        <Link
                            :href="`/home/pembanding/${recordId}`"
                            class="font-medium transition-colors hover:text-amber-600"
                        >
                            Detail #{{ recordId }}
                        </Link>
                        <i class="pi pi-chevron-right text-[9px] text-slate-300" />
                    </template>
                    <span class="font-semibold text-slate-600">
                        {{ isCreate ? "Tambah Data" : "Edit" }}
                    </span>
                </nav>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-2">

                    <!-- Dirty indicator — edit only -->
                    <Transition name="fade">
                        <span
                            v-if="!isCreate && isDirty"
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-600"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                            Ada perubahan belum disimpan
                        </span>
                    </Transition>

                    <template v-if="isCreate">
                        <Button
                            label="Simpan Data"
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
                            label="Simpan Perubahan"
                            icon="pi pi-save"
                            size="small"
                            :loading="processing"
                            :disabled="!isDirty"
                            @click="emit('submit')"
                        />
                    </template>
                </div>
            </div>

            <!-- Title + subtitle -->
            <div class="px-5 py-4">
                <h1 class="text-xl font-black tracking-tight text-slate-900">
                    {{ isCreate ? "Tambah Data Pembanding" : "Edit Data Pembanding" }}
                </h1>
                <p v-if="!isCreate && address" class="mt-0.5 line-clamp-1 text-sm text-slate-400">
                    {{ address }}
                </p>
                <p v-else-if="isCreate" class="mt-0.5 text-sm text-slate-400">
                    Isi keempat bagian di bawah ini untuk menambahkan data baru.
                </p>
            </div>

            <!-- Step progress -->
            <div class="border-t border-slate-100 px-5 pb-4 pt-3">
                <div class="flex items-center">
                    <template v-for="(step, idx) in steps" :key="step.key">
                        <!-- Step dot -->
                        <div class="flex flex-1 flex-col items-center gap-1.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold transition-all duration-200"
                                :class="idx < activeIndex()
                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                    : idx === activeIndex()
                                        ? 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-200'
                                        : 'border-slate-200 bg-white text-slate-400'"
                            >
                                <i v-if="idx < activeIndex()" class="pi pi-check" style="font-size: 10px" />
                                <i v-else :class="`pi ${step.icon}`" style="font-size: 10px" />
                            </div>
                            <span
                                class="hidden text-[10px] font-semibold transition-colors sm:block"
                                :class="idx === activeIndex()
                                    ? 'text-amber-600'
                                    : idx < activeIndex()
                                        ? 'text-emerald-600'
                                        : 'text-slate-400'"
                            >
                                {{ step.label }}
                            </span>
                        </div>

                        <!-- Connector line -->
                        <div
                            v-if="idx < steps.length - 1"
                            class="-mt-4 h-0.5 flex-1 transition-colors duration-300"
                            :class="idx < activeIndex() ? 'bg-emerald-400' : 'bg-slate-200'"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.flash-enter-active, .flash-leave-active { transition: all 0.3s ease; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateY(-8px); }
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>
