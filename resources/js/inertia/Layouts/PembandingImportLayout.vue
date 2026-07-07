<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import AdminLayout from "./AdminLayout.vue";
import TopNavLayout from "./TopNavLayout.vue";

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.importContext?.is_admin));
</script>

<template>
    <AdminLayout v-if="isAdmin" title="Bulk Import — Admin">
        <slot />
    </AdminLayout>
    <TopNavLayout v-else>
        <slot />
    </TopNavLayout>
</template>
