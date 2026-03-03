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

    <div class="py-5 flex justify-center">
        <div class="w-full max-w-5xl space-y-5">
            <PembandingHistoryBreadcrumb :record="record" />
            <PembandingHistoryHeader :record="record" :total="activities.length" />
            <PembandingHistoryActivityList :activities="activities" />
        </div>
    </div>
</template>
