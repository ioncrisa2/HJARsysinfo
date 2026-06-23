<script setup>
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import UiField from "../../../ui/UiField.vue";

const props = defineProps({
    form: { type: Object, required: true },
    isTanah: { type: Boolean, default: false },
    bangunanRequired: { type: Boolean, default: true },
    numConfig: { type: Object, default: () => ({}) },
});

const currentYear = new Date().getFullYear();

const normalizeYearInput = (event) => {
    const value = event.target.value.replace(/\D/g, "").slice(0, 4);
    event.target.value = value;
    props.form.tahun_bangun = value === "" ? null : Number(value);
};
</script>

<template>
    <div>
        <h3 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-400">Spesifikasi Teknis</h3>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <UiField id="luas_tanah" label="Luas Tanah" :required="true" :error="form.errors.luas_tanah">
            <InputNumber
                v-model="form.luas_tanah"
                inputId="luas_tanah"
                v-bind="numConfig"
                suffix=" m²"
                placeholder="0"
                class="w-full rounded-xl bg-slate-50/50"
            />
        </UiField>

        <UiField
            id="luas_bangunan"
            label="Luas Bangunan"
            :required="bangunanRequired"
            :error="form.errors.luas_bangunan"
            :help="isTanah ? 'Tidak perlu diisi untuk objek Tanah, Sawah, atau Tanah Kebun.' : ''"
        >
            <InputNumber
                v-model="form.luas_bangunan"
                inputId="luas_bangunan"
                v-bind="numConfig"
                suffix=" m²"
                placeholder="0"
                class="w-full rounded-xl bg-slate-50/50"
                :disabled="isTanah"
            />
        </UiField>

        <UiField id="lebar_depan" label="Lebar Depan" :required="true" :error="form.errors.lebar_depan">
            <InputNumber
                v-model="form.lebar_depan"
                inputId="lebar_depan"
                v-bind="numConfig"
                suffix=" m"
                placeholder="0"
                class="w-full rounded-xl bg-slate-50/50"
            />
        </UiField>

        <UiField id="lebar_jalan" label="Lebar Jalan" :required="true" :error="form.errors.lebar_jalan">
            <InputNumber
                v-model="form.lebar_jalan"
                inputId="lebar_jalan"
                v-bind="numConfig"
                suffix=" m"
                placeholder="0"
                class="w-full rounded-xl bg-slate-50/50"
            />
        </UiField>

        <UiField
            id="tahun_bangun"
            label="Tahun Bangun"
            :required="bangunanRequired"
            :error="form.errors.tahun_bangun"
            :help="isTanah ? 'Tidak perlu diisi untuk objek Tanah, Sawah, atau Tanah Kebun.' : ''"
        >
            <InputText
                v-model="form.tahun_bangun"
                id="tahun_bangun"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="4"
                placeholder="mis. 2010"
                class="w-full rounded-xl bg-slate-50/50 border-slate-200"
                :disabled="isTanah"
                :aria-invalid="Boolean(form.errors.tahun_bangun)"
                @input="normalizeYearInput"
            />
            <p class="mt-1 text-xs text-slate-400">Rentang tahun 1900-{{ currentYear }}.</p>
        </UiField>

        <UiField id="rasio_tapak" label="Rasio Tapak / FAR" :error="form.errors.rasio_tapak">
            <InputText
                v-model="form.rasio_tapak"
                id="rasio_tapak"
                placeholder="mis. 0.6"
                class="w-full rounded-xl bg-slate-50/50 border-slate-200"
            />
        </UiField>
        </div>
    </div>
</template>
