<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";
import UiField from "../../../ui/UiField.vue";
import UiSurface from "../../../ui/UiSurface.vue";

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: "create" },
});

const emit = defineEmits(["prev", "submit", "submit-and-create-another"]);

const isCreate = props.mode === "create";

const charLimit = 1000;
const charCount = computed(() => (props.form.catatan ?? "").length);
const charRemaining = computed(() => charLimit - charCount.value);

const checks = computed(() => [
    {
        label: "Jenis listing & objek",
        ok: Boolean(props.form.jenis_listing_id) && Boolean(props.form.jenis_objek_id),
    },
    {
        label: "Nama pemberi informasi",
        ok: Boolean(props.form.nama_pemberi_informasi?.trim()),
    },
    {
        label: "Tanggal data",
        ok: Boolean(props.form.tanggal_data),
    },
    {
        label: "Alamat & wilayah administratif",
        ok:
            Boolean(props.form.alamat_data?.trim()) &&
            Boolean(props.form.province_id) &&
            Boolean(props.form.regency_id) &&
            Boolean(props.form.district_id) &&
            Boolean(props.form.village_id),
    },
    {
        label: "Koordinat GPS",
        ok: Boolean(props.form.latitude) && Boolean(props.form.longitude),
    },
    {
        label: "Luas tanah",
        ok: props.form.luas_tanah !== null && props.form.luas_tanah !== "",
    },
    {
        label: "Harga",
        ok: props.form.harga !== null && props.form.harga !== "",
    },
]);

const checkedCount = computed(() => checks.value.filter((c) => c.ok).length);
const allChecked = computed(() => checkedCount.value === checks.value.length);
const missingChecks = computed(() => checks.value.filter((c) => !c.ok).map((c) => c.label));
</script>

<template>
    <div class="space-y-6 p-4 sm:p-5">
        <UiSectionHeader
            title="Catatan"
            subtitle="Tambahan informasi (opsional) dan ringkasan kelengkapan data."
            icon="pi pi-file-edit"
        />

        <UiField
            id="catatan"
            label="Catatan tambahan"
            :error="form.errors.catatan"
            help="Opsional. Maksimal 1000 karakter."
        >
            <div class="relative">
                <Textarea
                    v-model="form.catatan"
                    inputId="catatan"
                    auto-resize
                    rows="6"
                    :maxlength="charLimit"
                    placeholder="Tulis catatan di sini..."
                    class="w-full filter-light"
                />
                <span
                    class="pointer-events-none absolute bottom-2.5 right-3 text-[10px] font-semibold ui-tabular"
                    :class="charRemaining < 80 ? 'text-red-500' : 'text-slate-400'"
                >
                    {{ charCount }} / {{ charLimit }}
                </span>
            </div>
        </UiField>

        <UiSurface variant="inset" class="p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-balance text-sm font-semibold text-slate-900">Kelengkapan data</p>
                    <p class="text-pretty text-xs text-slate-500">
                        <span class="ui-tabular font-semibold text-slate-700">{{ checkedCount }} / {{ checks.length }}</span>
                        item wajib terisi.
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="allChecked ? 'border-slate-200 bg-white text-slate-700' : 'border-amber-200 bg-amber-50 text-amber-800'"
                >
                    <span class="size-1.5 rounded-full" :class="allChecked ? 'bg-slate-400' : 'bg-amber-500'" aria-hidden="true" />
                    {{ allChecked ? "Siap disimpan" : "Perlu dicek" }}
                </span>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <div
                    v-for="check in checks"
                    :key="check.label"
                    class="flex items-center gap-2 rounded-[var(--radius-sm)] border px-3 py-2 text-xs font-medium"
                    :class="check.ok ? 'border-slate-200 bg-white text-slate-700' : 'border-slate-200 bg-slate-50 text-slate-600'"
                >
                    <i :class="check.ok ? 'pi pi-check' : 'pi pi-circle'" class="text-[12px] text-slate-400" aria-hidden="true" />
                    <span class="min-w-0 truncate">{{ check.label }}</span>
                </div>
            </div>

            <div v-if="missingChecks.length" class="mt-4 rounded-[var(--radius-md)] border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-pretty text-xs font-medium text-amber-800">
                    Masih kosong:
                    <span class="font-semibold">{{ missingChecks.join(", ") }}</span>
                </p>
            </div>
        </UiSurface>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="emit('prev')" />

            <div class="flex flex-wrap items-center gap-2">
                <template v-if="isCreate">
                    <Button label="Simpan" icon="pi pi-save" :loading="form.processing" @click="emit('submit')" />
                    <Button
                        label="Simpan & Buat Lagi"
                        icon="pi pi-plus"
                        severity="secondary"
                        outlined
                        :loading="form.processing"
                        @click="emit('submit-and-create-another')"
                    />
                </template>
                <template v-else>
                    <Button label="Simpan" icon="pi pi-save" :loading="form.processing" @click="emit('submit')" />
                </template>
            </div>
        </div>
    </div>
</template>

