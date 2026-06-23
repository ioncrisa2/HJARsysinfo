<script setup>
import { ref, watch, nextTick } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    open: { type: Boolean, default: false },
    request: { type: Object, default: null },
});

const emit = defineEmits(["close"]);
const textareaRef = ref(null);

const form = useForm({
    reject_reason: "",
});

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            form.reset();
            await nextTick();
            textareaRef.value?.focus();
        }
    }
);

const close = () => {
    if (!form.processing) emit("close");
};

const submit = () => {
    if (!props.request) return;

    form.post(`/admin/data-contributor-registration-requests/${props.request.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => emit("close"),
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
            role="presentation"
            @keydown.esc="close"
        >
            <div
                class="w-full max-w-lg rounded-xl bg-white shadow-xl border border-slate-200"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reject-dialog-title"
            >
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 id="reject-dialog-title" class="text-base font-bold text-slate-900">
                        Tolak request data contributor
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Request dari {{ request?.display_name ?? "-" }} akan ditandai rejected.
                    </p>
                </div>

                <form class="space-y-4 px-5 py-4" @submit.prevent="submit">
                    <div>
                        <label for="reject_reason" class="text-sm font-semibold text-slate-700">
                            Alasan penolakan
                        </label>
                        <textarea
                            id="reject_reason"
                            ref="textareaRef"
                            v-model="form.reject_reason"
                            rows="4"
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            placeholder="Opsional, misalnya nomor tidak dapat dikonfirmasi."
                            :aria-describedby="form.errors.reject_reason ? 'reject-reason-error' : undefined"
                        />
                        <p
                            v-if="form.errors.reject_reason"
                            id="reject-reason-error"
                            class="mt-1 text-xs font-medium text-red-600"
                            role="alert"
                        >
                            {{ form.errors.reject_reason }}
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            :disabled="form.processing"
                            @click="close"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <i class="pi pi-times text-xs" aria-hidden="true" />
                            Tolak request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
