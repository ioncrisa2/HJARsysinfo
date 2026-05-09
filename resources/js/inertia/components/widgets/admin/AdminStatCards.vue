<script setup>
defineProps({
    stats: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Users -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-4">
                <div class="h-12 w-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="pi pi-users text-xl" />
                </div>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Total Users
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ stats.total_users ?? 0 }}</h3>
                <p class="text-sm text-slate-500 mt-1">Registered accounts</p>
            </div>
        </div>

        <!-- Pending Deletions -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-4">
                <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <i class="pi pi-shield text-xl" />
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Moderation
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ stats.pending_deletions ?? 0 }}</h3>
                <p class="text-sm text-slate-500 mt-1">Pending delete requests</p>
            </div>
        </div>

        <!-- Appraisal Data -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-4">
                <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="pi pi-database text-xl" />
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Bank Data
                </span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ stats.total_pembanding ?? 0 }}</h3>
                <p class="text-sm text-slate-500 mt-1">Total appraisal records</p>
            </div>
        </div>
    </div>
</template>
