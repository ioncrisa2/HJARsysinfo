<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

const props = defineProps({
    visible: { type: Boolean, default: false },
    count: { type: Number, default: 0 },
    format: { type: String, default: "excel" },
});

const emit = defineEmits(["update:visible", "confirm"]);

const visibleModel = computed({
    get: () => props.visible,
    set: (value) => emit("update:visible", value),
});

const formattedCount = computed(() =>
    (props.count ?? 0).toLocaleString("id-ID")
);
</script>

<template>
    <Dialog
        v-model:visible="visibleModel"
        :modal="true"
        :closable="false"
        header="Konfirmasi Export"
        style="width: 420px"
    >
        <div class="flex items-start gap-3 mb-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50">
                <i class="pi pi-download text-amber-500" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    Export {{ formattedCount }} data?
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                    File akan diunduh dalam format
                    <strong class="text-slate-700">{{ format.toUpperCase() }}</strong>.
                    Proses mungkin memerlukan beberapa saat tergantung jumlah data.
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button label="Batal" severity="secondary" outlined @click="visibleModel = false" />
            <Button label="Download" icon="pi pi-download" @click="emit('confirm')" />
        </div>
    </Dialog>
</template>
