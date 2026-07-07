<script setup>
import { computed } from "vue";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

const props = defineProps({
    visible: { type: Boolean, required: true },
    item: { type: Object, default: null },
    currentLevel: { type: String, required: true },
    levelNames: { type: Object, required: true },
    submitting: { type: Boolean, required: true },
});

const emit = defineEmits(["update:visible", "confirm"]);

const localVisible = computed({
    get: () => props.visible,
    set: (val) => emit("update:visible", val),
});
</script>

<template>
    <Dialog 
        v-model:visible="localVisible" 
        header="Konfirmasi Hapus" 
        :modal="true"
        class="w-full max-w-sm"
    >
        <div class="flex items-start gap-4 pt-2">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 shrink-0">
                <i class="pi pi-exclamation-triangle text-red-600 text-xl" />
            </div>
            <div>
                <p class="font-semibold text-slate-900">Hapus {{ levelNames[currentLevel] }}?</p>
                <p class="text-sm text-slate-600 mt-1">Anda yakin ingin menghapus <b>{{ item?.name }}</b>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
        </div>
        
        <template #footer>
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="localVisible = false" />
            <Button label="Hapus" icon="pi pi-trash" severity="danger" @click="emit('confirm')" :loading="submitting" />
        </template>
    </Dialog>
</template>
