<script setup>
import Button from "primevue/button";
import UiSectionHeader from "../../../ui/UiSectionHeader.vue";

import PropertyImageSection from "../sections/PropertyImageSection.vue";
import PropertyDimensionSection from "../sections/PropertyDimensionSection.vue";
import PropertyLegalitySection from "../sections/PropertyLegalitySection.vue";
import PropertyPricingSection from "../sections/PropertyPricingSection.vue";

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    mode: { type: String, default: "create" },
    imagePreview: { type: String, default: null },
    isTanah: { type: Boolean, default: false },
    bangunanRequired: { type: Boolean, default: true },
    numConfig: { type: Object, default: () => ({}) },
    currencyConfig: { type: Object, default: () => ({}) },
    handleImageUpload: { type: Function, default: null },
    clearImage: { type: Function, default: null },
});

const emit = defineEmits(["prev", "next"]);
</script>

<template>
    <div class="space-y-5 p-4 sm:p-5">
        <UiSectionHeader
            title="Spesifikasi Properti"
            subtitle="Detail teknis bangunan, karakteristik tanah, legalitas, dan informasi harga."
            icon="pi pi-building"
        />

        <!-- Image Upload Section -->
        <PropertyImageSection 
            :form="form" 
            :mode="mode" 
            :imagePreview="imagePreview" 
            :handleImageUpload="handleImageUpload" 
            :clearImage="clearImage" 
        />

        <!-- Technical Specs -->
        <PropertyDimensionSection 
            :form="form" 
            :isTanah="isTanah" 
            :bangunanRequired="bangunanRequired" 
            :numConfig="numConfig" 
        />

        <!-- Characteristics & Legality -->
        <PropertyLegalitySection 
            :form="form" 
            :options="options" 
        />

        <!-- Pricing Section -->
        <PropertyPricingSection 
            :form="form" 
            :options="options" 
            :currencyConfig="currencyConfig" 
            :numConfig="numConfig" 
        />

        <div class="flex justify-between border-t border-slate-100 pt-5">
            <Button label="Kembali" icon="pi pi-arrow-left" severity="secondary" outlined class="rounded-xl px-6" @click="emit('prev')" />
            <Button
                label="Lanjut ke Catatan"
                icon="pi pi-arrow-right"
                icon-pos="right"
                severity="primary"
                class="rounded-xl px-6"
                @click="emit('next')"
            />
        </div>
    </div>
</template>
