<script setup>
import { ref } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";
import { useDebouncedWatch } from "../../../composables/useDebouncedWatch";

const props = defineProps({
    tab: String,
    requestsPaginator: Object,
    trashedPaginator: Object,
    filters: Object,
    can: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || "");
const rejectForm = useForm({ review_note: "" });
const showRejectModal = ref(false);
const activeRequestId = ref(null);

useDebouncedWatch(search, (value) => {
    router.get("/admin/moderation", { search: value, tab: props.tab }, { preserveState: true, replace: true });
}, { delay: 300 });

const switchTab = (t) => {
    search.value = "";
    router.get("/admin/moderation", { tab: t }, { preserveState: false });
};

// Requests Actions
const approveReq = (id) => {
    confirm.require({
        message: 'Apakah Anda yakin ingin menyetujui permintaan penghapusan ini? Data properti akan dipindahkan ke Trash.',
        header: 'Konfirmasi Persetujuan',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(`/admin/moderation/approve/${id}`, {}, { preserveScroll: true });
        }
    });
};

const openRejectModal = (id) => {
    activeRequestId.value = id;
    showRejectModal.value = true;
};

const confirmReject = () => {
    rejectForm.post(`/admin/moderation/reject/${activeRequestId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            activeRequestId.value = null;
            rejectForm.reset();
        }
    });
};

// Trash Actions
const restoreData = (id) => {
    confirm.require({
        message: 'Kembalikan data properti ini ke database utama?',
        header: 'Konfirmasi Restore',
        icon: 'pi pi-refresh',
        accept: () => {
            router.post(`/admin/moderation/restore/${id}`, {}, { preserveScroll: true });
        }
    });
};

const forceDeleteData = (id) => {
    confirm.require({
        message: 'PERINGATAN: Tindakan ini akan menghapus data properti secara permanen dan tidak dapat dibatalkan. Lanjutkan?',
        header: 'Konfirmasi Hapus Permanen',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/admin/moderation/force-delete/${id}`, { preserveScroll: true });
        }
    });
};

const formatDate = (val) => {
    if (!val) return "-";
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (value) => {
    const amount = Number(value);

    if (!Number.isFinite(amount) || amount <= 0) {
        return null;
    }

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(amount);
};

const listingBadgeStyle = (listing) => ({
    backgroundColor: listing?.badge_color ? `${listing.badge_color}1A` : "#f1f5f9",
    borderColor: listing?.badge_color || "#cbd5e1",
    color: listing?.badge_color || "#475569",
});
</script>

<template>
    <AdminLayout title="Moderation Desk — Admin">
        <Head title="Moderation Desk" />

        <div class="mb-8 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Moderation Desk</h2>
                    <p class="text-sm text-slate-500 mt-1">Kelola permintaan penghapusan data dan pemulihan data terhapus.</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Tab Switcher -->
                <div class="flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                    <button
                        @click="switchTab('requests')"
                        class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                        :class="tab === 'requests' ? 'bg-white text-slate-900 shadow-md border border-slate-200' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <i class="pi pi-inbox mr-2" /> Pending Requests
                    </button>
                    <button
                        @click="switchTab('trash')"
                        class="px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                        :class="tab === 'trash' ? 'bg-white text-slate-900 shadow-md border border-slate-200' : 'text-slate-500 hover:text-slate-900'"
                    >
                        <i class="pi pi-trash mr-2" /> Trash Bin
                    </button>
                </div>

                <!-- Search -->
                <div class="relative w-full sm:max-w-xs">
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari berdasarkan alasan atau alamat..."
                        class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all bg-white shadow-sm"
                    />
                </div>
            </div>
        </div>

        <UiSurface class="overflow-hidden border-slate-200 shadow-xl shadow-slate-100 rounded-3xl">
            
            <!-- REQUESTS TAB -->
            <div class="overflow-x-auto" v-if="tab === 'requests'">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-widest text-[10px]">
                        <tr>
                            <th class="px-8 py-5">Target Properti</th>
                            <th class="px-8 py-5">Diminta Oleh</th>
                            <th class="px-8 py-5">Alasan Penghapusan</th>
                            <th class="px-8 py-5">Status</th>
                            <th class="px-8 py-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="req in requestsPaginator?.data" :key="req.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="max-w-xs">
                                    <p class="font-bold text-slate-900 truncate" :title="req.pembanding?.alamat_data">
                                        {{ req.pembanding?.alamat_data || 'Data Tidak Ditemukan' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-tighter">ID: #{{ req.id }} • {{ formatDate(req.created_at) }}</p>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="size-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 border border-slate-200">
                                        {{ (req.requestedBy?.name || '?').slice(0, 1).toUpperCase() }}
                                    </div>
                                    <span class="font-bold text-slate-700 text-xs">{{ req.requestedBy?.name || 'Unknown User' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-xs text-slate-600 whitespace-normal line-clamp-2 max-w-sm italic leading-relaxed">
                                    "{{ req.reason }}"
                                </p>
                            </td>
                            <td class="px-8 py-5">
                                <Tag 
                                    v-if="req.status === 'pending'" 
                                    value="Pending Review" 
                                    class="bg-amber-50 text-amber-600 border border-amber-100 text-[9px] font-black uppercase tracking-widest px-2.5 py-1" 
                                />
                                <Tag 
                                    v-else-if="req.status === 'approved'" 
                                    value="Disetujui" 
                                    class="bg-emerald-50 text-emerald-600 border border-emerald-100 text-[9px] font-black uppercase tracking-widest px-2.5 py-1" 
                                />
                                <Tag 
                                    v-else 
                                    value="Ditolak" 
                                    class="bg-red-50 text-red-600 border border-red-100 text-[9px] font-black uppercase tracking-widest px-2.5 py-1" 
                                />
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex items-center justify-end gap-2" v-if="req.status === 'pending' && (props.can.approve || props.can.reject)">
                                    <Button
                                        v-if="props.can.approve"
                                        icon="pi pi-check"
                                        severity="success"
                                        text
                                        rounded
                                        v-tooltip.top="'Setujui Penghapusan'"
                                        @click="approveReq(req.id)"
                                        class="hover:bg-emerald-50"
                                    />
                                    <Button
                                        v-if="props.can.reject"
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        rounded
                                        v-tooltip.top="'Tolak Permintaan'"
                                        @click="openRejectModal(req.id)"
                                        class="hover:bg-red-50"
                                    />
                                </div>
                                <div v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                    Review oleh {{ req.reviewedBy?.name || 'Admin' }}
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!requestsPaginator?.data.length">
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-300">
                                    <i class="pi pi-inbox text-5xl mb-4 opacity-20" />
                                    <p class="text-sm font-bold">Tidak ada permintaan penghapusan pending.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TRASH TAB -->
            <div class="overflow-x-auto" v-if="tab === 'trash'">
                <table class="w-full min-w-[980px] table-fixed text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-widest text-[10px]">
                        <tr>
                            <th class="w-[42%] px-8 py-5">Properti Terhapus</th>
                            <th class="w-[18%] px-6 py-5">Dihapus Oleh</th>
                            <th class="w-[28%] px-6 py-5">Alasan</th>
                            <th class="w-[12%] px-6 py-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="res in trashedPaginator?.data" :key="res.id" class="align-top transition-colors hover:bg-slate-50/70">
                            <td class="px-8 py-5">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl border border-red-100 bg-red-50 text-red-500">
                                            <i class="pi pi-trash text-sm" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-900" :title="res.alamat_data">
                                                {{ res.alamat_data || 'Alamat tidak tersedia' }}
                                            </p>
                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                <span
                                                    class="inline-flex max-w-[180px] items-center rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider"
                                                    :style="listingBadgeStyle(res.jenis_listing)"
                                                >
                                                    <span class="truncate">{{ res.jenis_listing?.name || 'Tanpa Listing' }}</span>
                                                </span>
                                                <span v-if="formatCurrency(res.harga)" class="text-xs font-semibold text-slate-500">
                                                    {{ formatCurrency(res.harga) }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400">
                                                Dihapus {{ formatDate(res.deleted_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-[11px] font-black text-slate-500">
                                        {{ (res.deleted_by?.name || '?').slice(0, 1).toUpperCase() }}
                                    </div>
                                    <span class="min-w-0 truncate text-xs font-bold text-slate-700" :title="res.deleted_by?.name || 'Unknown Admin'">
                                        {{ res.deleted_by?.name || 'Unknown Admin' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <p class="line-clamp-3 text-xs italic leading-6 text-slate-600" :title="res.deleted_reason || '-'">
                                    "{{ res.deleted_reason || '-' }}"
                                </p>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div v-if="props.can.restore || props.can.forceDelete" class="flex items-center justify-end gap-2">
                                    <Button
                                        v-if="props.can.restore"
                                        icon="pi pi-refresh"
                                        severity="info"
                                        text
                                        rounded
                                        aria-label="Pulihkan data"
                                        v-tooltip.top="'Pulihkan Data (Restore)'"
                                        @click="restoreData(res.id)"
                                        class="shrink-0 border border-blue-100 bg-blue-50 hover:bg-blue-100"
                                    />
                                    <Button
                                        v-if="props.can.forceDelete"
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        aria-label="Hapus permanen"
                                        v-tooltip.top="'Hapus Permanen'"
                                        @click="forceDeleteData(res.id)"
                                        class="shrink-0 border border-red-100 bg-red-50 hover:bg-red-100"
                                    />
                                </div>
                                <span v-else class="text-[10px] font-black uppercase tracking-widest text-slate-300">
                                    No Action
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!trashedPaginator?.data.length">
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-300">
                                    <i class="pi pi-trash text-5xl mb-4 opacity-20" />
                                    <p class="text-sm font-bold">Tempat sampah kosong.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Shared Pagination -->
            <div 
                v-if="(tab === 'requests' ? requestsPaginator?.links : trashedPaginator?.links)?.length > 3" 
                class="px-8 py-5 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-50/50"
            >
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Menampilkan Halaman {{ (tab === 'requests' ? requestsPaginator : trashedPaginator).current_page }} dari {{ (tab === 'requests' ? requestsPaginator : trashedPaginator).last_page }}
                </span>
                <div class="flex gap-1.5 overflow-x-auto max-w-full">
                    <template v-for="(link, i) in (tab === 'requests' ? requestsPaginator.links : trashedPaginator.links)" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url + (link.url?.includes('?') ? '&' : '?') + 'tab=' + tab"
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs rounded-xl transition-all font-bold"
                            :class="[
                                link.active ? 'bg-slate-900 text-white shadow-lg shadow-slate-300' : 'text-slate-600 hover:bg-white hover:shadow-sm border border-transparent hover:border-slate-200',
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs rounded-xl font-bold opacity-20 cursor-not-allowed border border-transparent text-slate-400"
                        />
                    </template>
                </div>
            </div>
        </UiSurface>

        <!-- Reject Modal -->
        <Dialog v-model:visible="showRejectModal" modal header="Tolak Permintaan Penghapusan" :style="{ width: '30vw' }" :breakpoints="{ '960px': '75vw', '641px': '100vw' }">
            <div class="p-2 space-y-4">
                <div class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                    <i class="pi pi-exclamation-circle text-red-600 mt-0.5" />
                    <div>
                        <p class="text-xs font-bold text-red-800">Perhatian</p>
                        <p class="text-[11px] text-red-600 mt-1">Harap berikan alasan yang jelas mengapa permintaan ini ditolak agar surveyor dapat memahami alasannya.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="review_note" class="text-xs font-black text-slate-400 uppercase tracking-widest">Catatan Penolakan</label>
                    <Textarea 
                        id="review_note"
                        v-model="rejectForm.review_note" 
                        class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:bg-white transition-all min-h-[120px] text-sm" 
                        required 
                        placeholder="Tulis alasan penolakan di sini..."
                    />
                    <p v-if="rejectForm.errors.review_note" class="text-xs text-red-500 font-bold italic">{{ rejectForm.errors.review_note }}</p>
                </div>
            </div>
            <template #footer>
                <div class="flex items-center justify-end gap-3 pt-4">
                    <Button label="Batal" severity="secondary" text class="rounded-xl px-6" @click="showRejectModal = false" />
                    <Button 
                        label="Konfirmasi Tolak" 
                        severity="danger" 
                        class="rounded-xl px-6 shadow-lg shadow-red-100" 
                        :loading="rejectForm.processing"
                        @click="confirmReject" 
                    />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>
