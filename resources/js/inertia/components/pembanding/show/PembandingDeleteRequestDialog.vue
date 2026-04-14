<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

const props = defineProps({
    visible: { type: Boolean, default: false },
    reason: { type: String, default: "" },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(["update:visible", "update:reason", "submit"]);

const visibleModel = computed({
    get: () => props.visible,
    set: (value) => emit("update:visible", value),
});

const canSubmit = computed(() => !props.processing && props.reason.trim().length > 0);

const close = () => {
    if (props.processing) return;
    visibleModel.value = false;
};
</script>

<template>
    <Dialog
        v-model:visible="visibleModel"
        :modal="true"
        :draggable="false"
        :closable="false"
        :dismissableMask="false"
        :closeOnEscape="!processing"
        style="width: min(560px, 100%)"
    >
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-balance text-base font-semibold text-slate-900">Request Hapus Data</h3>
                    <p class="mt-1 text-pretty text-xs text-slate-500">
                        Isi alasan penghapusan. Request ini akan dievaluasi oleh admin.
                    </p>
                </div>

                <button
                    type="button"
                    class="ui-hit inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-2 text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                    :disabled="processing"
                    aria-label="Tutup dialog"
                    @click="close"
                >
                    <i class="pi pi-times text-[12px]" aria-hidden="true" />
                </button>
            </div>
        </template>

        <div class="space-y-2">
            <label for="delete-request-reason" class="block text-xs font-semibold text-slate-700">
                Alasan penghapusan
            </label>
            <textarea
                id="delete-request-reason"
                :value="reason"
                rows="5"
                maxlength="1000"
                :disabled="processing"
                class="w-full rounded-[var(--radius-md)] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition
                    disabled:bg-slate-50 disabled:text-slate-500"
                placeholder="Contoh: data duplikat, data tidak valid, atau salah input."
                @input="emit('update:reason', $event.target.value)"
            />
            <p class="text-pretty text-xs text-slate-500">Maksimal 1000 karakter.</p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Batal" severity="secondary" outlined :disabled="processing" @click="close" />
                <Button
                    label="Kirim Request"
                    icon="pi pi-send"
                    severity="danger"
                    :loading="processing"
                    :disabled="!canSubmit"
                    @click="emit('submit')"
                />
            </div>
        </template>
    </Dialog>
</template>

