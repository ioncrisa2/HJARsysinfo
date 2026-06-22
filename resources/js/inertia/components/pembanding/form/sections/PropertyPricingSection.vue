<script setup>
import { computed } from "vue";
import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";
import UiField from "../../../ui/UiField.vue";

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    currencyConfig: { type: Object, default: () => ({}) },
    numConfig: { type: Object, default: () => ({}) },
});

const isSewa = computed(() => {
    const listingId = props.form.jenis_listing_id;
    if (!listingId || !props.options?.jenisListings) return false;
    const listing = props.options.jenisListings.find((item) => item.value == listingId);
    return listing?.label?.toLowerCase() === "sewa";
});

const rentPeriodLabel = computed(() => {
    if (!isSewa.value || !props.form.jangka_waktu_sewa || !props.form.satuan_waktu_sewa) {
        return null;
    }

    const duration = Number(props.form.jangka_waktu_sewa);
    const unit = String(props.form.satuan_waktu_sewa).toLowerCase();

    if (duration === 1 && unit === "bulan") return "per bulan";
    if (duration === 1 && unit === "tahun") return "per tahun";

    return `per ${duration} ${unit}`;
});

const toNumber = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
};

const harga = computed(() => toNumber(props.form.harga));
const luasTanah = computed(() => toNumber(props.form.luas_tanah));
const luasBangunan = computed(() => toNumber(props.form.luas_bangunan));

const showHargaPerMeter = computed(() => (
    !isSewa.value &&
    harga.value > 0 &&
    luasTanah.value > 0 &&
    luasBangunan.value <= 0
));

const hargaPerMeter = computed(() => {
    if (!showHargaPerMeter.value) {
        return null;
    }

    return harga.value / luasTanah.value;
});

const formatCurrency = (value) => new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
}).format(value || 0);
</script>

<template>
    <div class="border-t border-slate-100 pt-5">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-400">Nilai Properti</h3>

        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            <UiField
                id="harga"
                :label="isSewa ? 'Harga Sewa' : 'Harga Penawaran/Transaksi'"
                :required="true"
                :error="form.errors.harga"
                :help="isSewa ? 'Nominal harga sewa untuk periode di samping: per bulan, per beberapa bulan, atau per tahun.' : 'Nilai total properti.'"
            >
                <InputNumber
                    v-model="form.harga"
                    inputId="harga"
                    v-bind="currencyConfig"
                    placeholder="Rp 0"
                    class="w-full rounded-xl bg-slate-50/50 border-slate-200 text-slate-900 font-bold"
                />
            </UiField>

            <template v-if="isSewa">
                <UiField
                    id="jangka_waktu_sewa"
                    label="Periode Harga Sewa"
                    :required="true"
                    :error="form.errors.jangka_waktu_sewa"
                    help="Isi 1 untuk per bulan/per tahun, atau beberapa bulan seperti 3, 6, 12."
                >
                    <InputNumber
                        v-model="form.jangka_waktu_sewa"
                        inputId="jangka_waktu_sewa"
                        v-bind="numConfig"
                        placeholder="1"
                        class="w-full rounded-xl bg-slate-50/50 border-slate-200 text-slate-900"
                    />
                </UiField>

                <UiField
                    id="satuan_waktu_sewa"
                    label="Satuan"
                    :required="true"
                    :error="form.errors.satuan_waktu_sewa"
                >
                    <Select
                        v-model="form.satuan_waktu_sewa"
                        :options="[{ label: 'Bulan', value: 'Bulan' }, { label: 'Tahun', value: 'Tahun' }]"
                        option-label="label"
                        option-value="value"
                        placeholder="Pilih..."
                        class="w-full rounded-xl bg-slate-50/50 border-slate-200 text-slate-900"
                        inputId="satuan_waktu_sewa"
                    />
                </UiField>

                <p v-if="rentPeriodLabel" class="md:col-span-3 text-sm font-semibold text-amber-700">
                    Harga sewa akan disimpan sebagai {{ formatCurrency(form.harga) }} {{ rentPeriodLabel }}.
                </p>
            </template>

            <div v-else-if="showHargaPerMeter" class="flex flex-col justify-end pb-1.5">
                <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Estimasi per m²</p>
                <p class="text-base font-bold text-slate-800">
                    {{ formatCurrency(hargaPerMeter) }}
                    <span class="text-xs font-normal text-slate-500">/m² tanah</span>
                </p>
                <p class="mt-1 text-xs text-slate-400">Dihitung dari harga dibagi luas tanah.</p>
            </div>
        </div>
    </div>
</template>
