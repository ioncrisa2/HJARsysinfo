<script setup>
import { computed } from "vue";

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    reason: {
        type: String,
        default: "",
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:visible", "update:reason", "submit"]);

const canSubmit = computed(() => !props.processing && props.reason.trim().length > 0);

const close = () => {
    if (props.processing) return;
    emit("update:visible", false);
};
</script>

<template>
    <Transition name="delete-request-fade">
        <div v-if="visible" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="close" />

            <div class="relative w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Request Hapus Data</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Isi alasan penghapusan. Request ini akan dievaluasi oleh super_admin tanpa PIN.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-2 py-1 text-slate-500 transition hover:bg-slate-50"
                        @click="close"
                    >
                        <i class="pi pi-times text-xs" />
                    </button>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Alasan Penghapusan</label>
                    <textarea
                        :value="reason"
                        rows="5"
                        maxlength="1000"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        placeholder="Contoh: data duplikat, data tidak valid, atau salah input."
                        @input="$emit('update:reason', $event.target.value)"
                    />
                    <p class="mt-2 text-xs text-slate-400">
                        Maksimal 1000 karakter.
                    </p>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        :disabled="processing"
                        @click="close"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="!canSubmit"
                        @click="$emit('submit')"
                    >
                        <i v-if="processing" class="pi pi-spin pi-spinner text-xs" />
                        Kirim Request
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.delete-request-fade-enter-active,
.delete-request-fade-leave-active {
    transition: opacity 0.2s ease;
}

.delete-request-fade-enter-from,
.delete-request-fade-leave-to {
    opacity: 0;
}
</style>
