<script setup>
import { Head, router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";
import { computed, ref } from "vue";
import AppLayout from "../../Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    submission: { type: Object, required: true },
    candidates: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const selectedId = ref(props.candidates[0]?.id ?? null);
const processing = ref(false);
const selected = computed(() => props.candidates.find((candidate) => candidate.id === selectedId.value) ?? null);
const newValues = computed(() => Object.fromEntries(props.submission.rows.map((row) => [row.key, row.value])));
const comparedRows = computed(() => (selected.value?.rows ?? []).map((row) => ({
    ...row,
    newValue: newValues.value[row.key] ?? "—",
    different: row.value !== (newValues.value[row.key] ?? "—"),
})));
const differenceCount = computed(() => comparedRows.value.filter((row) => row.different).length);

const useExisting = () => {
    if (!selected.value || selected.value.deleted) return;

    confirm.require({
        header: "Gunakan data lama?",
        message: `Data baru tidak akan disimpan. Record #${selected.value.id} tetap digunakan tanpa perubahan.`,
        icon: "pi pi-info-circle",
        acceptLabel: "Gunakan Data Lama",
        rejectLabel: "Batal",
        accept: () => {
            processing.value = true;
            router.post(`/app/pembanding/duplicate-reviews/${props.submission.id}/use-existing/${selected.value.id}`, {}, {
                onFinish: () => (processing.value = false),
            });
        },
    });
};

const replaceExisting = () => {
    if (!selected.value?.can_update) return;

    confirm.require({
        header: "Perbarui record lama?",
        message: `Record #${selected.value.id} akan diperbarui menggunakan data baru. Perubahan dicatat dalam riwayat aktivitas.`,
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Perbarui Data Lama",
        rejectLabel: "Batal",
        accept: () => {
            processing.value = true;
            router.put(`/app/pembanding/duplicate-reviews/${props.submission.id}/replace/${selected.value.id}`, {}, {
                onFinish: () => (processing.value = false),
            });
        },
    });
};
</script>

<template>
    <Head title="Konfirmasi Duplikasi Data Pembanding" />

    <div class="space-y-4 py-4">
        <header class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 sm:px-5">
            <div class="flex items-start gap-3">
                <i class="pi pi-copy mt-1 text-amber-700" aria-hidden="true" />
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Konfirmasi diperlukan</p>
                    <h1 class="mt-1 text-xl font-bold text-slate-950">Data identik sudah tersedia</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-700">
                        Record baru belum dibuat. Pilih kandidat lama, periksa perbandingannya, lalu tentukan data yang akan digunakan.
                        Input sementara disimpan sampai {{ submission.expires_at }}.
                    </p>
                </div>
            </div>
        </header>

        <section v-if="candidates.length > 1" aria-labelledby="candidate-heading" class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 id="candidate-heading" class="font-bold text-slate-900">Kandidat duplikat</h2>
                <Tag :value="`${candidates.length} kandidat`" severity="warn" />
            </div>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="candidate in candidates"
                    :key="candidate.id"
                    type="button"
                    :aria-pressed="selectedId === candidate.id"
                    class="min-h-11 rounded-lg border px-3 py-2 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                    :class="selectedId === candidate.id ? 'border-amber-500 bg-amber-50' : 'border-slate-200 hover:border-slate-400'"
                    @click="selectedId = candidate.id"
                >
                    <span class="block text-sm font-bold text-slate-900">Record #{{ candidate.id }}</span>
                    <span class="mt-0.5 block text-xs text-slate-600">{{ candidate.created_by }} · {{ candidate.updated_at }}</span>
                </button>
            </div>
        </section>

        <section v-if="selected" class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="comparison-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                <div>
                    <h2 id="comparison-heading" class="font-bold text-slate-950">Record #{{ selected.id }} dibandingkan dengan input baru</h2>
                    <p class="mt-0.5 text-sm text-slate-600">
                        {{ differenceCount === 0 ? "Semua field identik." : `${differenceCount} field berbeda.` }}
                    </p>
                </div>
                <Tag v-if="selected.deleted" value="Record telah dihapus" severity="danger" />
                <Tag v-else-if="differenceCount === 0" value="Sama persis" severity="success" />
                <Tag v-else :value="`${differenceCount} perbedaan`" severity="warn" />
            </div>

            <div class="grid gap-3 border-b border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 sm:p-5">
                <figure class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <figcaption class="border-b border-slate-200 px-3 py-2 text-sm font-bold text-slate-800">Data lama</figcaption>
                    <img :src="selected.image_url" alt="Foto properti pada data lama" class="h-48 w-full object-cover" />
                </figure>
                <figure class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <figcaption class="border-b border-slate-200 px-3 py-2 text-sm font-bold text-slate-800">Input baru</figcaption>
                    <img :src="submission.image_url" alt="Foto properti pada input baru" class="h-48 w-full object-cover" />
                </figure>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="w-1/4 px-4 py-3 font-bold">Field</th>
                            <th scope="col" class="w-[37.5%] px-4 py-3 font-bold">Data lama</th>
                            <th scope="col" class="w-[37.5%] px-4 py-3 font-bold">Input baru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in comparedRows" :key="row.key" :class="row.different ? 'bg-amber-50' : ''">
                            <th scope="row" class="px-4 py-3 font-semibold text-slate-700">{{ row.label }}</th>
                            <td class="break-words px-4 py-3 text-slate-900">{{ row.value }}</td>
                            <td class="break-words px-4 py-3 text-slate-900">
                                {{ row.newValue }}
                                <span v-if="row.different" class="ml-2 font-bold text-amber-800">Berbeda</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <dl class="divide-y divide-slate-100 md:hidden">
                <div v-for="row in comparedRows" :key="row.key" class="p-4" :class="row.different ? 'bg-amber-50' : ''">
                    <dt class="font-bold text-slate-800">{{ row.label }}</dt>
                    <dd class="mt-2 grid gap-2 text-sm">
                        <div><span class="block text-xs font-semibold text-slate-500">Data lama</span>{{ row.value }}</div>
                        <div><span class="block text-xs font-semibold text-slate-500">Input baru</span>{{ row.newValue }}</div>
                    </dd>
                </div>
            </dl>
        </section>

        <div v-if="selected" class="sticky bottom-3 z-10 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur sm:flex sm:items-center sm:justify-between sm:gap-4">
            <p v-if="!selected.can_update && !selected.deleted" class="mb-3 text-sm text-amber-800 sm:mb-0">
                Anda tidak memiliki izin untuk memperbarui record milik user lain.
            </p>
            <p v-else-if="selected.deleted" class="mb-3 text-sm text-red-700 sm:mb-0">
                Record ini telah dihapus dan tidak dapat dipilih sebelum dipulihkan oleh user berwenang.
            </p>
            <span v-else class="hidden sm:block" />
            <div class="grid gap-2 sm:flex sm:justify-end">
                <Button
                    label="Gunakan Data Lama"
                    icon="pi pi-database"
                    severity="secondary"
                    outlined
                    :disabled="processing || selected.deleted"
                    class="min-h-11"
                    @click="useExisting"
                />
                <Button
                    v-if="selected.can_update"
                    label="Perbarui Data Lama"
                    icon="pi pi-refresh"
                    :loading="processing"
                    class="min-h-11"
                    @click="replaceExisting"
                />
            </div>
        </div>
    </div>
</template>
