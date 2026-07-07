<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "../../Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    batches: { type: Object, required: true },
});
const page = usePage();
const fileInput = ref(null);
const form = useForm({ file: null });
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
const baseUrl = "/app/pembanding-imports";

const statusClass = (status) => ({
    draft: "bg-amber-100 text-amber-900",
    processing: "bg-blue-100 text-blue-900",
    partial: "bg-orange-100 text-orange-900",
    complete: "bg-emerald-100 text-emerald-900",
    completed: "bg-emerald-100 text-emerald-900",
    failed: "bg-red-100 text-red-900",
}[status] ?? "bg-slate-100 text-slate-800");

const statusIcon = (status) => ({
    draft: "pi-file-edit",
    processing: "pi-spin pi-spinner",
    partial: "pi-exclamation-triangle",
    complete: "pi-check-circle",
    completed: "pi-check-circle",
    failed: "pi-times-circle",
}[status] ?? "pi-info-circle");

const actionLabel = (status) => ({
    draft: "Lanjutkan melengkapi",
    processing: "Lihat perkembangan",
    partial: "Periksa hasil",
    complete: "Lihat hasil",
    completed: "Lihat hasil",
    failed: "Periksa hasil",
}[status] ?? "Buka unggahan");

const progressText = (batch) => {
    if (batch.status === "processing") {
        return `${batch.imported_rows} berhasil · ${batch.processing_rows} sedang diproses`;
    }
    if (["complete", "completed"].includes(batch.status)) {
        return `${batch.imported_rows} data berhasil dimasukkan`;
    }
    if (["partial", "failed"].includes(batch.status)) {
        return `${batch.imported_rows} berhasil · ${batch.failed_rows} perlu diperbaiki`;
    }
    return `${batch.ready_rows} dari ${batch.selected_rows} data pilihan sudah lengkap`;
};

const chooseFile = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.file = file;
    form.clearErrors("file");
};

const submit = () => {
    form.post(baseUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = "";
        },
    });
};
</script>

<template>
    <Head title="Masukkan Data dari Excel" />

    <div class="space-y-5">
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Data Pembanding</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Masukkan data dari Excel</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Data dari Excel disimpan sebagai draf. Belum ada data yang masuk ke Data Pembanding sebelum Anda memeriksa dan melengkapinya.
                </p>
            </div>
        </header>

        <div v-if="success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
            <i class="pi pi-check-circle mr-2" aria-hidden="true" />{{ success }}
        </div>
        <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
            <i class="pi pi-times-circle mr-2" aria-hidden="true" />{{ error }}
        </div>

        <ol class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4" aria-label="Tahapan memasukkan data dari Excel">
            <li v-for="(step, index) in ['Unggah Excel', 'Pilih data', 'Periksa dan lengkapi', 'Masukkan ke Data Pembanding']" :key="step" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">{{ index + 1 }}</span>
                <span class="text-sm font-semibold text-slate-700">{{ step }}</span>
            </li>
        </ol>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="upload-heading">
            <div class="max-w-3xl">
                <h2 id="upload-heading" class="text-lg font-bold text-slate-900">1. Unggah Excel P2PK</h2>
                <p class="mt-1 text-sm text-slate-600">Gunakan file .xlsx atau .xlsm dengan sheet Data_Pembanding. Maksimal 500 data dan ukuran 10 MB.</p>

                <form class="mt-5 space-y-3" @submit.prevent="submit">
                    <label for="p2pk-file" class="block text-sm font-semibold text-slate-800">File Excel</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input
                            id="p2pk-file"
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xlsm"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            :aria-invalid="Boolean(form.errors.file)"
                            :aria-describedby="form.errors.file ? 'p2pk-file-error' : 'p2pk-file-help'"
                            @change="chooseFile"
                        />
                        <button
                            type="submit"
                            class="min-h-11 shrink-0 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!form.file || form.processing"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner mr-2" aria-hidden="true" />
                            {{ form.processing ? 'Sedang membaca...' : 'Baca dan simpan sebagai draf' }}
                        </button>
                    </div>
                    <p id="p2pk-file-help" class="text-xs text-slate-500">File tidak akan langsung membuat Data Pembanding.</p>
                    <p v-if="form.errors.file" id="p2pk-file-error" class="text-sm font-semibold text-red-700" role="alert">{{ form.errors.file }}</p>
                </form>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="history-heading">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="history-heading" class="text-lg font-bold text-slate-900">Riwayat unggahan Excel</h2>
                <p class="mt-1 text-sm text-slate-600">Buka kembali draf untuk melihat data yang perlu diperiksa.</p>
            </div>

            <div v-if="props.batches.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3">File</th>
                            <th class="px-5 py-3">Pemilik</th>
                            <th class="px-5 py-3">Jumlah data</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="batch in props.batches.data" :key="batch.id" class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="max-w-xs truncate font-semibold text-slate-900" :title="batch.filename">{{ batch.filename }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">Terakhir diperbarui {{ batch.updated_at }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-600">{{ progressText(batch) }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ batch.owner }}</td>
                            <td class="px-5 py-4 tabular-nums text-slate-700">{{ batch.total_rows }}</td>
                            <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(batch.status)"><i class="pi" :class="statusIcon(batch.status)" aria-hidden="true" />{{ batch.status_label }}</span></td>
                            <td class="px-5 py-4 text-right"><Link :href="`${baseUrl}/${batch.id}`" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-amber-800 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">{{ actionLabel(batch.status) }}</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="px-5 py-10 text-center">
                <i class="pi pi-file-excel text-3xl text-slate-300" aria-hidden="true" />
                <p class="mt-3 font-semibold text-slate-800">Belum ada unggahan Excel</p>
                <p class="mt-1 text-sm text-slate-500">Unggah file P2PK pertama untuk membuat draf.</p>
            </div>

            <nav v-if="props.batches.links?.length > 3" class="flex flex-wrap justify-center gap-1 border-t border-slate-200 px-5 py-4" aria-label="Halaman riwayat unggahan">
                <Link v-for="link in props.batches.links" :key="link.label" :href="link.url ?? '#'" preserve-scroll class="rounded-md px-3 py-2 text-sm" :class="[link.active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" />
            </nav>
        </section>
    </div>
</template>
