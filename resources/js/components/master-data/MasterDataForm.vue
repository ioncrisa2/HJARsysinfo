<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import UiField from "../ui/UiField.vue";

const props = defineProps({
    visible: { type: Boolean, required: true },
    form: { type: Object, required: true },
    editingRecord: { type: Object, default: null },
    label: { type: String, required: true },
    supportsBadgeColor: { type: Boolean, default: false },
});

const emit = defineEmits(["update:visible", "submit"]);

const dialogVisible = computed({
    get: () => props.visible,
    set: (val) => emit("update:visible", val),
});

const slugPreview = computed(() => {
    const value = props.form.name
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-|-$/g, "");

    return value || "slug-otomatis";
});
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        :header="editingRecord ? `Edit ${label}` : `Tambah ${label}`"
        style="width: min(520px, 100%)"
    >
        <form class="space-y-4" @submit.prevent="emit('submit')">
            <UiField id="master_name" label="Nama Data" required :error="form.errors.name">
                <InputText id="master_name" v-model="form.name" class="w-full" placeholder="Contoh: Sertifikat Hak Milik" />
            </UiField>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <p class="text-xs font-semibold text-slate-500">Slug otomatis</p>
                <p class="ui-tabular mt-1 text-sm font-bold text-slate-900">{{ slugPreview }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField id="master_order" label="Urutan" :error="form.errors.sort_order" help="Kosongkan saat create untuk memakai urutan terakhir.">
                    <InputNumber
                        v-model="form.sort_order"
                        input-id="master_order"
                        class="w-full"
                        input-class="w-full"
                        :min="0"
                        placeholder="Otomatis"
                    />
                </UiField>

                <div class="space-y-2">
                    <p class="text-xs font-semibold text-slate-600">Status</p>
                    <div class="flex items-center gap-2 pt-2">
                        <Checkbox v-model="form.is_active" input-id="master_active" binary />
                        <label for="master_active" class="text-sm font-medium text-slate-700">Aktif</label>
                    </div>
                </div>
            </div>

            <UiField v-if="supportsBadgeColor" id="master_badge" label="Warna Badge" :error="form.errors.badge_color">
                <div class="flex items-center gap-3">
                    <input
                        id="master_badge"
                        v-model="form.badge_color"
                        type="color"
                        class="size-10 cursor-pointer rounded-lg border border-slate-200 bg-white"
                    />
                    <InputText v-model="form.badge_color" class="w-full font-mono text-xs" placeholder="#64748b" />
                </div>
            </UiField>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <Button label="Batal" severity="secondary" outlined :disabled="form.processing" @click="dialogVisible = false" />
                <Button
                    :label="editingRecord ? 'Simpan Perubahan' : 'Tambah Data'"
                    icon="pi pi-save"
                    type="submit"
                    :loading="form.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
