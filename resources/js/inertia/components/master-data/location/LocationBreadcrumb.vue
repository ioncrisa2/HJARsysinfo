<script setup>
import { computed } from "vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";

const props = defineProps({
    path: { type: Array, required: true },
    searchQuery: { type: String, default: "" },
    currentLevel: { type: String, required: true },
    levelNames: { type: Object, required: true },
});

const emit = defineEmits(["update:searchQuery", "navigate-up", "add"]);

const localSearchQuery = computed({
    get: () => props.searchQuery,
    set: (val) => emit("update:searchQuery", val),
});
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 p-4">
        <!-- Breadcrumbs -->
        <div class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
            <button 
                @click="emit('navigate-up', -1)" 
                class="flex items-center gap-1.5 hover:text-amber-600 transition-colors"
                :class="path.length === 0 ? 'text-amber-600' : 'text-slate-500'"
            >
                <i class="pi pi-map text-xs" />
                Provinsi
            </button>
            
            <template v-for="(p, index) in path" :key="p.id">
                <i class="pi pi-chevron-right text-[10px] text-slate-400" />
                <button 
                    @click="emit('navigate-up', index)"
                    class="hover:text-amber-600 transition-colors"
                    :class="index === path.length - 1 ? 'text-amber-600' : 'text-slate-500'"
                >
                    {{ p.name }}
                </button>
            </template>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <span class="relative">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
                <InputText 
                    v-model="localSearchQuery" 
                    placeholder="Cari nama..." 
                    class="pl-9 w-full md:w-48 rounded-xl text-sm" 
                />
            </span>
            <Button 
                :label="`Tambah ${levelNames[currentLevel]}`" 
                icon="pi pi-plus" 
                class="rounded-xl px-4 text-sm font-bold"
                @click="emit('add')"
            />
        </div>
    </div>
</template>
