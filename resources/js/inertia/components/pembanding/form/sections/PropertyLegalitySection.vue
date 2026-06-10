<script setup>
import Select from "primevue/select";
import UiField from "../../../ui/UiField.vue";

defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const kondisiFields = [
    { key: "bentuk_tanah_id", label: "Bentuk tanah", opts: "bentukTanahs" },
    { key: "posisi_tanah_id", label: "Posisi tanah", opts: "posisiTanahs" },
    { key: "kondisi_tanah_id", label: "Kondisi tanah", opts: "kondisiTanahs" },
    { key: "topografi_id", label: "Topografi", opts: "topografis" },
    { key: "dokumen_tanah_id", label: "Dokumen tanah", opts: "dokumenTanahs" },
    { key: "peruntukan_id", label: "Peruntukan", opts: "peruntukans" },
];
</script>

<template>
    <div class="border-t border-slate-100 pt-5">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Karakteristik & Legalitas</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <UiField
                v-for="field in kondisiFields"
                :key="field.key"
                :id="field.key"
                :label="field.label"
                :required="true"
                :error="form.errors[field.key]"
            >
                <Select
                    v-model="form[field.key]"
                    :options="options[field.opts] ?? []"
                    option-label="label"
                    option-value="value"
                    placeholder="Pilih nilai..."
                    class="w-full rounded-xl bg-slate-50/50"
                    :inputId="field.key"
                    filter
                />
            </UiField>
        </div>
    </div>
</template>
