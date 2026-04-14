<script setup>
import { Head } from "@inertiajs/vue3";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";
import PembandingHistoryBreadcrumb from "../../components/pembanding/history/PembandingHistoryBreadcrumb.vue";
import PembandingHistoryHeader from "../../components/pembanding/history/PembandingHistoryHeader.vue";
import PembandingHistoryActivityList from "../../components/pembanding/history/PembandingHistoryActivityList.vue";

defineOptions({ layout: TopNavLayout });

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
    <Head :title="`Riwayat #${record.id}`" />

    <div class="space-y-4 py-3 sm:py-5">
        <PembandingHistoryBreadcrumb :record="record" />
        <PembandingHistoryHeader :record="record" :total="activities.length" />
        <PembandingHistoryActivityList :activities="activities" />
    </div>
</template>
