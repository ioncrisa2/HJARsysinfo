<script setup>
import { ref, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import UiSurface from "../../../components/ui/UiSurface.vue";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    tab: String,
    requestsPaginator: Object,
    trashedPaginator: Object,
    filters: Object,
});

const confirm = useConfirm();
const search = ref(props.filters.search || "");
const rejectForm = useForm({ review_note: "" });
const showRejectModal = ref(false);
const activeRequestId = ref(null);

let searchTimeout = null;
watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get("/admin/moderation", { search: value, tab: props.tab }, { preserveState: true, replace: true });
    }, 300);
});

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
                                <div class="flex items-center justify-end gap-2" v-if="req.status === 'pending'">
                                    <Button
                                        icon="pi pi-check"
                                        severity="success"
                                        text
                                        rounded
                                        v-tooltip.top="'Setujui Penghapusan'"
                                        @click="approveReq(req.id)"
                                        class="hover:bg-emerald-50"
                                    />
                                    <Button
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
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-widest text-[10px]">
                        <tr>
                            <th class="px-8 py-5">Properti Terhapus</th>
                            <th class="px-8 py-5">Dihapus Oleh</th>
                            <th class="px-8 py-5">Alasan</th>
                            <th class="px-8 py-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="res in trashedPaginator?.data" :key="res.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="max-w-xs">
                                    <p class="font-bold text-slate-900 truncate" :title="res.alamat_data">{{ res.alamat_data }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Tag :value="res.jenis_listing" class="bg-slate-100 text-slate-600 text-[8px] font-black uppercase px-2 py-0.5 border border-slate-200" />
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Dihapus {{ formatDate(res.deleted_at) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="size-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 border border-slate-200">
                                        {{ (res.deletedBy?.name || '?').slice(0, 1).toUpperCase() }}
                                    </div>
                                    <span class="font-bold text-slate-700 text-xs">{{ res.deletedBy?.name || 'Unknown Admin' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-xs text-slate-600 whitespace-normal line-clamp-2 max-w-sm italic leading-relaxed">
                                    "{{ res.deleted_reason || '-' }}"
                                </p>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <Button
                                        icon="pi pi-refresh"
                                        severity="info"
                                        text
                                        rounded
                                        v-tooltip.top="'Pulihkan Data (Restore)'"
                                        @click="restoreData(res.id)"
                                        class="hover:bg-blue-50"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        v-tooltip.top="'Hapus Permanen'"
                                        @click="forceDeleteData(res.id)"
                                        class="hover:bg-red-50"
                                    />
                                </div>
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

