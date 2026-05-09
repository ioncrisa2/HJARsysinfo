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
    <div class="space-y-8 p-6 sm:p-8">
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
                    rows="6"
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

        <UiSurface variant="inset" class="p-6 bg-slate-50 rounded-2xl border border-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm font-bold text-slate-900">Checklist Kelengkapan Data</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="font-bold text-amber-600">{{ checkedCount }} dari {{ checks.length }}</span> item wajib telah terisi.
                    </p>
                </div>
                <div
                    class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-[10px] font-black uppercase tracking-widest transition-all duration-500"
                    :class="allChecked ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                >
                    <i :class="allChecked ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'" />
                    {{ allChecked ? "Data Lengkap" : "Data Belum Lengkap" }}
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="check in checks"
                    :key="check.label"
                    class="flex items-center gap-3 rounded-xl border p-3 text-xs font-bold transition-all duration-300"
                    :class="check.ok ? 'border-green-100 bg-green-50/30 text-green-700' : 'border-slate-200 bg-white text-slate-400 opacity-60'"
                >
                    <div class="size-5 rounded-full flex items-center justify-center transition-all duration-500" :class="check.ok ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-300'">
                        <i :class="check.ok ? 'pi pi-check' : 'pi pi-circle'" class="text-[10px]" />
                    </div>
                    <span class="min-w-0 truncate">{{ check.label }}</span>
                </div>
            </div>

            <div v-if="missingChecks.length" class="mt-6 rounded-xl border border-amber-200 bg-amber-50/50 p-4 animate-pulse">
                <p class="text-[11px] font-bold text-amber-800 flex items-start gap-2">
                    <i class="pi pi-info-circle mt-0.5" />
                    <span>
                        Mohon lengkapi: <span class="font-black underline">{{ missingChecks.join(", ") }}</span>
                    </span>
                </p>
            </div>
        </UiSurface>

        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-8">
            <Button label="Kembali ke Properti" icon="pi pi-arrow-left" severity="secondary" outlined class="rounded-xl px-6" @click="emit('prev')" />

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="isCreate">
                    <Button
                        label="Simpan & Buat Lagi"
                        icon="pi pi-plus"
                        severity="secondary"
                        class="rounded-xl px-6"
                        :loading="form.processing"
                        @click="emit('submit-and-create-another')"
                    />
                    <Button label="Simpan Data" icon="pi pi-save" class="rounded-xl px-10 shadow-lg shadow-slate-200" :loading="form.processing" @click="emit('submit')" />
                </template>
                <template v-else>
                    <Button label="Simpan Perubahan" icon="pi pi-save" class="rounded-xl px-10 shadow-lg shadow-slate-200" :loading="form.processing" @click="emit('submit')" />
                </template>
            </div>
        </div>
    </div>
</template>

