<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";
import UiField from "../../../ui/UiField.vue";
import { getMissingFields, getTabContext, TAB_META, TAB_ORDER } from "../../../../config/pembandingFormRequiredFields";

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: "create" },
    options: { type: Object, default: () => ({}) },
    isTanah: { type: Boolean, default: false },
    isSewa: { type: Boolean, default: false },
});

const emit = defineEmits(["prev", "submit", "submit-and-create-another"]);

const isCreate = props.mode === "create";

const charLimit = 1000;
const charCount = computed(() => (props.form.catatan ?? "").length);
const charRemaining = computed(() => charLimit - charCount.value);

const requiredContext = computed(() => getTabContext(props.form, props.options, {
    mode: props.mode,
    isTanah: props.isTanah,
    isSewa: props.isSewa,
}));

const missingFieldsByTab = computed(() => getMissingFields(props.form, requiredContext.value));
const missingTabs = computed(() => TAB_ORDER
    .map((tab) => ({
        tab,
        meta: TAB_META[tab],
        fields: missingFieldsByTab.value[tab] ?? [],
    }))
    .filter((item) => item.fields.length > 0));
const missingCount = computed(() => missingTabs.value.reduce((total, item) => total + item.fields.length, 0));
const allChecked = computed(() => missingCount.value === 0);
</script>

<template>
    <div class="space-y-5 p-4 sm:p-5">
        <UiSectionHeader
            title="Catatan & Review"
            subtitle="Tambahkan informasi pendukung dan periksa kembali kelengkapan data sebelum disimpan."
            icon="pi pi-file-edit"
        />

        <UiField
            id="catatan"
            label="Catatan Tambahan"
            :error="form.errors.catatan"
            help="Opsional. Maksimal 1000 karakter."
        >
            <div class="relative group">
                <Textarea
                    v-model="form.catatan"
                    inputId="catatan"
                    auto-resize
                    rows="4"
                    :maxlength="charLimit"
                    placeholder="Tulis catatan penting di sini, misalnya detail akses jalan, kondisi lingkungan sekitar, atau alasan harga tinggi/rendah..."
                    class="w-full rounded-xl bg-slate-50/50 border-slate-200 group-hover:bg-white transition-all"
                />
                <span
                    class="pointer-events-none absolute bottom-3 right-4 text-[10px] font-bold uppercase tracking-widest"
                    :class="charRemaining < 80 ? 'text-red-500' : 'text-slate-400'"
                >
                    {{ charCount }} / {{ charLimit }}
                </span>
            </div>
        </UiField>

        <div v-if="!allChecked" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 flex items-start gap-4">
            <div class="bg-amber-100 p-2 rounded-full flex items-center justify-center">
                <i class="pi pi-exclamation-triangle text-amber-600 text-lg" />
            </div>
            <div>
                <p class="text-sm font-bold text-amber-900">Data Belum Lengkap</p>
                <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                    Mohon kembali ke tab sebelumnya dan lengkapi {{ missingCount }} isian wajib berikut:
                </p>
                <div class="mt-2 space-y-1">
                    <p v-for="item in missingTabs" :key="item.tab" class="text-xs leading-relaxed text-amber-800">
                        <span class="font-bold">{{ item.meta.label }}:</span>
                        {{ item.fields.map((field) => field.label).join(", ") }}
                    </p>
                </div>
            </div>
        </div>
        <div v-else class="rounded-2xl border border-green-200 bg-green-50 p-5 flex items-start gap-4">
            <div class="bg-green-100 p-2 rounded-full flex items-center justify-center">
                <i class="pi pi-check-circle text-green-600 text-lg" />
            </div>
            <div>
                <p class="text-sm font-bold text-green-900">Data Lengkap</p>
                <p class="text-xs text-green-700 mt-1">
                    Semua item wajib telah terisi. Anda dapat menyimpan data ini sekarang.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-5">
            <Button label="Kembali ke Properti" icon="pi pi-arrow-left" severity="secondary" outlined class="rounded-xl px-6" @click="emit('prev')" />

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="isCreate">
                    <Button
                        label="Simpan & Buat Lagi"
                        icon="pi pi-plus"
                        severity="secondary"
                        class="rounded-xl px-6"
                        :loading="form.processing"
                        :disabled="form.processing"
                        @click="emit('submit-and-create-another')"
                    />
                    <Button label="Simpan Data" icon="pi pi-save" class="rounded-xl px-10 shadow-lg shadow-slate-200" :loading="form.processing" :disabled="form.processing" @click="emit('submit')" />
                </template>
                <template v-else>
                    <Button label="Simpan Perubahan" icon="pi pi-save" class="rounded-xl px-10 shadow-lg shadow-slate-200" :loading="form.processing" :disabled="form.processing" @click="emit('submit')" />
                </template>
            </div>
        </div>
    </div>
</template>
