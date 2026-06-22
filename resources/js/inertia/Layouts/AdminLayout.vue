<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";

import AdminSidebar from "../components/admin/layout/AdminSidebar.vue";
import AdminTopbar from "../components/admin/layout/AdminTopbar.vue";

// ── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    title: { type: String, default: "Admin Control Panel" },
});

// ── Page & Auth ──────────────────────────────────────────────────────────────
const page = usePage();
const toast = useToast();
const PREFIX = "/admin";
const user = computed(() => page.props.auth?.user ?? {});

// ── Flash Messages → Toast ───────────────────────────────────────────────────
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

watch(flashSuccess, (msg) => {
    if (msg) toast.add({ severity: "success", summary: "Success", detail: msg, life: 4000 });
});
watch(flashError, (msg) => {
    if (msg) toast.add({ severity: "error", summary: "Error", detail: msg, life: 5000 });
});

// Show initial flash on mount
onMounted(() => {
    if (flashSuccess.value) toast.add({ severity: "success", summary: "Success", detail: flashSuccess.value, life: 4000 });
    if (flashError.value) toast.add({ severity: "error", summary: "Error", detail: flashError.value, life: 5000 });
});

// ── Sidebar State ────────────────────────────────────────────────────────────
const sidebarOpen = ref(true);
const mobileOverlay = ref(false);
const autoCollapseBreakpoint = 1024;

const toggleSidebar = () => {
    if (window.innerWidth < 768) {
        mobileOverlay.value = !mobileOverlay.value;
    } else {
        sidebarOpen.value = !sidebarOpen.value;
    }
};

const closeMobileMenu = () => {
    mobileOverlay.value = false;
};

const setInitialSidebarState = () => {
    if (window.innerWidth >= 768 && window.innerWidth < autoCollapseBreakpoint) {
        sidebarOpen.value = false;
    }
};

// Close mobile overlay on route navigation
router.on("navigate", () => {
    mobileOverlay.value = false;
});

// Handle resize: close mobile overlay when going to desktop
const handleResize = () => {
    if (window.innerWidth >= 768) {
        mobileOverlay.value = false;
    }
};

onMounted(() => {
    setInitialSidebarState();
    window.addEventListener("resize", handleResize);
});
onUnmounted(() => window.removeEventListener("resize", handleResize));

// ── Breadcrumbs ──────────────────────────────────────────────────────────────
const breadcrumbs = computed(() => {
    const url = page.url.split("?")[0];
    const segments = url.replace(PREFIX, "").split("/").filter(Boolean);

    const crumbs = [{ label: "Admin", href: PREFIX, icon: "pi-home" }];

    // Map known segments to labels
    const labelMap = {
        users: "Users",
        "access-control": "Access Control",
        create: "Create",
        edit: "Edit",
        moderation: "Moderation",
        pembanding: "Appraisal Data",
        "master-data": "Master Data",
        geo: "Geo Data",
        export: "Export",
        backup: "Backup",
        search: "Search",
        profile: "Profile",
        settings: "Settings",
        "activity-logs": "Activity Logs",
    };

    let path = PREFIX;
    segments.forEach((seg, i) => {
        path += `/${seg}`;
        const isLast = i === segments.length - 1;
        // If segment is a number (record ID), label it as detail
        const label = /^\d+$/.test(seg) ? `#${seg}` : (labelMap[seg] ?? seg.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()));
        crumbs.push({
            label,
            href: isLast ? null : path,
        });
    });

    return crumbs;
});
</script>

<template>
    <Head :title="title" />

    <div class="flex h-dvh bg-slate-50 overflow-hidden">
        <Toast position="top-right" />
        <ConfirmDialog />

        <!-- Mobile Overlay Backdrop -->
        <Transition name="fade">
            <div
                v-if="mobileOverlay"
                class="fixed inset-0 bg-black/50 z-40 md:hidden"
                @click="closeMobileMenu"
            />
        </Transition>

        <!-- Sidebar -->
        <AdminSidebar 
            :sidebarOpen="sidebarOpen" 
            :mobileOverlay="mobileOverlay" 
        />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar Header -->
            <AdminTopbar 
                :breadcrumbs="breadcrumbs" 
                :user="user" 
                :sidebarOpen="sidebarOpen"
                @toggleSidebar="toggleSidebar" 
            />

            <!-- Slot for page-level header (optional) -->
            <div v-if="$slots.header" class="bg-white border-b border-slate-200 px-4 md:px-6 py-4">
                <slot name="header" />
            </div>

            <!-- Main Page Content -->
            <main id="main-content" class="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Fade transition for mobile overlay backdrop */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
