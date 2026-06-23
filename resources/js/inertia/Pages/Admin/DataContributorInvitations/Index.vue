<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import DataContributorInviteTable from "../../../components/admin/data-contributor/DataContributorInviteTable.vue";
import DataContributorRequestTable from "../../../components/admin/data-contributor/DataContributorRequestTable.vue";
import DataContributorRejectDialog from "../../../components/admin/data-contributor/DataContributorRejectDialog.vue";

const props = defineProps({
    registrationRequests: { type: Object, required: true },
    invitations: { type: Object, required: true },
    activeTab: { type: String, default: "requests" },
    generatedInvitationUrl: { type: String, default: null },
    generatedInvitationToken: { type: String, default: null },
    can: { type: Object, default: () => ({}) },
});

const generateForm = useForm({});
const activeTab = ref(props.activeTab);
const copiedTarget = ref(null);
const copyFailed = ref(false);
const rejectTarget = ref(null);

const generateInvitation = () => {
    generateForm.post("/admin/data-contributor-invitations", {
        preserveScroll: true,
    });
};

const copyText = async (value, target = "link") => {
    if (!value) return;

    copyFailed.value = false;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(value);
        } else {
            fallbackCopy(value);
        }

        copiedTarget.value = target;
        setTimeout(() => (copiedTarget.value = null), 2000);
    } catch {
        try {
            fallbackCopy(value);
            copiedTarget.value = target;
            setTimeout(() => (copiedTarget.value = null), 2000);
        } catch {
            copyFailed.value = true;
        }
    }
};

const fallbackCopy = (value) => {
    const textarea = document.createElement("textarea");
    textarea.value = value;
    textarea.setAttribute("readonly", "");
    textarea.style.position = "fixed";
    textarea.style.top = "-9999px";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    const copiedToClipboard = document.execCommand("copy");
    document.body.removeChild(textarea);

    if (!copiedToClipboard) {
        throw new Error("Copy command failed");
    }
};

const acceptRequest = (request) => {
    router.post(`/admin/data-contributor-registration-requests/${request.id}/accept`, {}, {
        preserveScroll: true,
    });
};

const rejectRequest = (request) => {
    rejectTarget.value = request;
};
</script>

<template>
    <AdminLayout title="Data Contributor Invitations — Admin">
        <div class="space-y-5">
            <section class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-600">Internal registration</p>
                    <h1 class="mt-1 text-xl font-bold text-slate-950">Data Contributor Invitations</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Generate link registrasi terbatas untuk kontributor data. link ini berlaku selama 7 hari.
                    </p>
                </div>
                <button
                    v-if="can.manage"
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="generateForm.processing"
                    @click="generateInvitation"
                >
                    <i class="pi pi-send text-xs" aria-hidden="true" />
                    Generate invitation link
                </button>
            </section>

            <section
                v-if="generatedInvitationUrl"
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm"
                aria-live="polite"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-amber-900">Invitation link baru</p>
                        <p class="mt-1 break-all font-mono text-xs text-amber-800">{{ generatedInvitationUrl }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-amber-300 bg-white px-3 text-sm font-bold text-amber-800 hover:bg-amber-100"
                        @click="copyText(generatedInvitationUrl, 'link')"
                    >
                        <i :class="copiedTarget === 'link' ? 'pi pi-check' : 'pi pi-copy'" class="text-xs" aria-hidden="true" />
                        {{ copiedTarget === "link" ? "Copied" : "Copy link" }}
                    </button>
                </div>
                <div
                    v-if="generatedInvitationToken"
                    class="mt-3 rounded-lg border border-amber-200 bg-white/70 p-3"
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-900">Raw token</p>
                            <p class="mt-1 break-all font-mono text-xs text-amber-800">{{ generatedInvitationToken }}</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-amber-300 bg-white px-3 text-sm font-bold text-amber-800 hover:bg-amber-100"
                            @click="copyText(generatedInvitationToken, 'token')"
                        >
                            <i :class="copiedTarget === 'token' ? 'pi pi-check' : 'pi pi-copy'" class="text-xs" aria-hidden="true" />
                            {{ copiedTarget === "token" ? "Copied" : "Copy token" }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-amber-800">
                        Token mentah ini hanya tampil sekali. Setelah pindah halaman atau refresh, token tidak bisa dilihat ulang.
                    </p>
                </div>
                <p v-if="copyFailed" class="mt-2 text-xs font-semibold text-red-700" role="alert">
                    Browser menolak akses clipboard. Blok link lalu salin manual.
                </p>
            </section>

            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition-colors"
                            :class="activeTab === 'requests' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                            @click="activeTab = 'requests'"
                        >
                            Registration Requests
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition-colors"
                            :class="activeTab === 'tokens' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                            @click="activeTab = 'tokens'"
                        >
                            Invitation Tokens
                        </button>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <span>{{ registrationRequests.total }} requests</span>
                        <span class="text-slate-300">/</span>
                        <span>{{ invitations.total }} invitations</span>
                    </div>
                </div>

                <div v-if="activeTab === 'requests'" class="space-y-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Registration Requests</h2>
                        <p class="mt-1 text-xs text-slate-500">Data calon contributor yang sudah submit form registrasi.</p>
                    </div>
                    <DataContributorRequestTable
                        :requests="registrationRequests"
                        @accept="acceptRequest"
                        @reject="rejectRequest"
                    />
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Invitation Tokens</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Token mentah hanya tampil sekali saat dibuat. Tabel ini menampilkan fingerprint hash dan status pemakaian.
                        </p>
                    </div>
                    <DataContributorInviteTable :invitations="invitations" />
                </div>
            </section>
        </div>

        <DataContributorRejectDialog
            :open="Boolean(rejectTarget)"
            :request="rejectTarget"
            @close="rejectTarget = null"
        />
    </AdminLayout>
</template>
