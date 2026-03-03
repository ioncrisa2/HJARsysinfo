<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Textarea from "primevue/textarea";

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: "create" },
});

const emit = defineEmits(["prev", "submit", "submit-and-create-another"]);

const isCreate = props.mode === "create";

const charLimit     = 2000;
const charCount     = computed(() => (props.form.catatan ?? "").length);
const charRemaining = computed(() => charLimit - charCount.value);

// Checklist field wajib lintas tab
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
        ok: Boolean(props.form.alamat_data?.trim()) &&
            Boolean(props.form.province_id) &&
            Boolean(props.form.regency_id),
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
const allChecked   = computed(() => checkedCount.value === checks.value.length);
</script>

<template>
    <div class="space-y-6 p-5">

        <!-- ── Catatan ─────────────────────────────────── -->
        <section>
            <div class="mb-3 flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                    <i class="pi pi-file-edit text-amber-600" style="font-size: 12px" />
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-700">Catatan Tambahan</h2>
                    <p class="text-[11px] text-slate-400">Informasi tambahan yang belum tercakup di bagian lain.</p>
                </div>
            </div>

            <div class="relative">
                <Textarea
                    v-model="form.catatan"
                    auto-resize
                    rows="6"
                    :maxlength="charLimit"
                    placeholder="Tulis catatan di sini... (opsional)"
                    class="w-full"
                />
                <span
                    class="pointer-events-none absolute bottom-2.5 right-3 text-[10px] font-semibold tabular-nums"
                    :class="charRemaining < 100 ? 'text-red-400' : 'text-slate-300'"
                >
                    {{ charCount }} / {{ charLimit }}
                </span>
            </div>
        </section>

        <hr class="border-slate-100" />

        <!-- ── Kelengkapan Data ───────────────────────── -->
        <section>
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                        :class="allChecked ? 'bg-emerald-100' : 'bg-slate-100'"
                    >
                        <i
                            :class="allChecked ? 'pi pi-check-circle text-emerald-600' : 'pi pi-list-check text-slate-400'"
                            style="font-size: 12px"
                        />
                    </div>
                    <h2 class="text-sm font-bold text-slate-700">Kelengkapan Data</h2>
                </div>
                <span
                    class="text-xs font-bold tabular-nums"
                    :class="allChecked ? 'text-emerald-600' : 'text-amber-600'"
                >
                    {{ checkedCount }} / {{ checks.length }} terisi
                </span>
            </div>

            <!-- Progress bar -->
            <div class="mb-4 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="allChecked ? 'bg-emerald-500' : 'bg-amber-400'"
                    :style="{ width: `${(checkedCount / checks.length) * 100}%` }"
                />
            </div>

            <!-- Checklist grid -->
            <div class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="check in checks"
                    :key="check.label"
                    class="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors"
                    :class="check.ok
                        ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                        : 'border-slate-100 bg-slate-50 text-slate-500'"
                >
                    <i
                        :class="check.ok ? 'pi pi-check-circle text-emerald-500' : 'pi pi-circle text-slate-300'"
                        style="font-size: 12px"
                    />
                    {{ check.label }}
                </div>
            </div>

            <!-- Callout -->
            <div
                class="mt-4 flex items-center gap-3 rounded-xl border px-4 py-3"
                :class="allChecked ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"
            >
                <i
                    :class="allChecked ? 'pi pi-check-circle text-emerald-500' : 'pi pi-exclamation-triangle text-amber-500'"
                    style="font-size: 16px"
                />
                <p class="text-xs font-medium" :class="allChecked ? 'text-emerald-700' : 'text-amber-700'">
                    <template v-if="allChecked">
                        Semua field wajib sudah terisi. Data siap untuk disimpan.
                    </template>
                    <template v-else>
                        Ada {{ checks.length - checkedCount }} field yang belum terisi. Silakan periksa kembali sebelum menyimpan.
                    </template>
                </p>
            </div>
        </section>

        <!-- Nav + Submit -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined @click="emit('prev')" />

            <div class="flex flex-wrap items-center gap-2">
                <template v-if="isCreate">
                    <Button
                        label="Simpan Data"
                        icon="pi pi-save"
                        :loading="form.processing"
                        @click="emit('submit')"
                    />
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
                    <Button
                        label="Simpan Perubahan"
                        icon="pi pi-save"
                        :loading="form.processing"
                        @click="emit('submit')"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
