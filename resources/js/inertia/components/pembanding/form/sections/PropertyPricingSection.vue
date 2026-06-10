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
    const listing = props.options.jenisListings.find(l => l.value == listingId);
    return listing?.label?.toLowerCase() === 'sewa';
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
</script>

<template>
    <div class="border-t border-slate-100 pt-5">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Nilai Properti</h3>
        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200">
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                <UiField id="harga" :label="isSewa ? 'Harga Sewa' : 'Harga Penawaran/Transaksi'" :required="true" :error="form.errors.harga" :help="isSewa ? 'Nominal harga sewa untuk periode di samping: per bulan, per beberapa bulan, atau per tahun.' : 'Nilai total properti.'">
                    <InputNumber
                        v-model="form.harga"
                        inputId="harga"
                        v-bind="currencyConfig"
                        placeholder="Rp 0"
                        class="w-full rounded-xl bg-white border-slate-200 text-slate-900 font-bold"
                    />
                </UiField>
                
                <template v-if="isSewa">
                    <UiField id="jangka_waktu_sewa" label="Periode Harga Sewa" :required="true" :error="form.errors.jangka_waktu_sewa" help="Isi 1 untuk per bulan/per tahun, atau beberapa bulan seperti 3, 6, 12.">
                        <InputNumber
                            v-model="form.jangka_waktu_sewa"
                            inputId="jangka_waktu_sewa"
                            v-bind="numConfig"
                            placeholder="1"
                            class="w-full rounded-xl bg-white border-slate-200 text-slate-900"
                        />
                    </UiField>

                    <UiField id="satuan_waktu_sewa" label="Satuan" :required="true" :error="form.errors.satuan_waktu_sewa">
                        <Select
                            v-model="form.satuan_waktu_sewa"
                            :options="[{label:'Bulan', value:'Bulan'}, {label:'Tahun', value:'Tahun'}]"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih..."
                            class="w-full rounded-xl bg-white border-slate-200 text-slate-900"
                            inputId="satuan_waktu_sewa"
                        />
                    </UiField>

                    <div v-if="rentPeriodLabel" class="md:col-span-3 rounded-xl border border-amber-400/30 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                        Harga sewa akan disimpan sebagai {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(form.harga || 0) }} {{ rentPeriodLabel }}.
                    </div>
                </template>
                
                <div v-else-if="form.harga && form.luas_tanah" class="flex flex-col justify-end pb-1.5">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Estimasi per m²</p>
                    <p class="text-lg font-bold text-amber-400">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(form.harga / form.luas_tanah) }}<span class="text-slate-500 text-xs font-normal"> /m²</span></p>
                </div>
            </div>
        </div>
    </div>
</template>
