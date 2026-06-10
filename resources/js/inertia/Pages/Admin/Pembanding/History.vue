<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import PembandingHistoryBreadcrumb from "../../../components/pembanding/history/PembandingHistoryBreadcrumb.vue";
import PembandingHistoryHeader from "../../../components/pembanding/history/PembandingHistoryHeader.vue";
import PembandingHistoryActivityList from "../../../components/pembanding/history/PembandingHistoryActivityList.vue";

defineOptions({ layout: AdminLayout });

defineProps({
    record: {
        type: Object,
        default: () => ({}),
    },
    activities: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Riwayat #${record.id} — Admin`" />

    <div class="space-y-4 py-3 sm:py-5">
        <PembandingHistoryBreadcrumb :record="record" is-admin />
        <PembandingHistoryHeader :record="record" :total="activities.length" />
        <PembandingHistoryActivityList :activities="activities" />
    </div>
</template>
