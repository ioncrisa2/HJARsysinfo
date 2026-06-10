<script setup>
import { computed } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";

const props = defineProps({
    visible: { type: Boolean, required: true },
    mode: { type: String, required: true }, // "add" | "edit"
    currentLevel: { type: String, required: true },
    levelNames: { type: Object, required: true },
    form: { type: Object, required: true }, // { id, name, original_id }
    submitting: { type: Boolean, required: true },
});

const emit = defineEmits(["update:visible", "save"]);

const localVisible = computed({
    get: () => props.visible,
    set: (val) => emit("update:visible", val),
});

const localForm = computed(() => props.form);
</script>

<template>
    <Dialog 
        v-model:visible="localVisible" 
        :header="mode === 'add' ? `Tambah ${levelNames[currentLevel]}` : `Edit ${levelNames[currentLevel]}`" 
        :modal="true"
        class="p-fluid w-full max-w-md"
    >
        <div class="space-y-4 pt-2">
            <div v-if="mode === 'add' && currentLevel === 'province'" class="space-y-1">
                <label class="text-sm font-semibold text-slate-700">Kode Provinsi (2 digit)</label>
                <InputText v-model="localForm.id" placeholder="mis. 11" maxlength="2" autofocus />
            </div>
            <div v-else-if="mode === 'edit' && currentLevel === 'province'" class="space-y-1">
                <label class="text-sm font-semibold text-slate-700">Kode Provinsi</label>
                <InputText :model-value="localForm.id" disabled class="bg-slate-50" />
            </div>

            <div class="space-y-1">
                <label class="text-sm font-semibold text-slate-700">Nama {{ levelNames[currentLevel] }}</label>
                <InputText v-model="localForm.name" placeholder="Masukkan nama..." @keyup.enter="emit('save')" :autofocus="currentLevel !== 'province' || mode === 'edit'" />
            </div>
        </div>
        
        <template #footer>
            <Button label="Batal" icon="pi pi-times" text severity="secondary" @click="localVisible = false" />
            <Button label="Simpan" icon="pi pi-check" @click="emit('save')" :loading="submitting" />
        </template>
    </Dialog>
</template>
