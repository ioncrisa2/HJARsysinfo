<script setup>
import { computed, nextTick, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";

import AppSidebar from "../components/layout/AppSidebar.vue";
import AppTopbar from "../components/layout/AppTopbar.vue";

// ── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    title: { type: String, default: "Bank Data KJPP HJA'R" },
});

// ── Page & Auth ──────────────────────────────────────────────────────────────
const page = usePage();
const toast = useToast();
const PREFIX = "/app";
const user = computed(() => page.props.auth?.user ?? {});

// ── Flash Messages → Toast ───────────────────────────────────────────────────
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

watch(flashSuccess, (msg) => {
    if (msg) toast.add({ severity: "success", summary: "Success", detail: msg, life: 4000 });
});
watch(flashError, (msg) => {
    if (msg) toast.add({ severity: "error", summary: "Gagal", detail: msg });
});

// Show initial flash on mount
onMounted(() => {
    if (flashSuccess.value) toast.add({ severity: "success", summary: "Success", detail: flashSuccess.value, life: 4000 });
    if (flashError.value) toast.add({ severity: "error", summary: "Gagal", detail: flashError.value });
});

// ── Sidebar State ────────────────────────────────────────────────────────────
const sidebarOpen = ref(true);
const mobileOverlay = ref(false);
const isMobile = ref(false);
const autoCollapseBreakpoint = 1024;
let mobileMenuTrigger = null;
let removeNavigateListener = null;

const sidebarExpanded = computed(() => isMobile.value ? mobileOverlay.value : sidebarOpen.value);
const sidebarInert = computed(() => isMobile.value && !mobileOverlay.value);

const focusableElements = () => Array.from(document.querySelectorAll(
    '#app-sidebar a[href], #app-sidebar button:not([disabled]), #app-sidebar input:not([disabled]), #app-sidebar [tabindex]:not([tabindex="-1"])',
));

const focusMobileMenu = async () => {
    await nextTick();
    focusableElements()[0]?.focus();
};

const toggleSidebar = () => {
    if (isMobile.value) {
        if (mobileOverlay.value) {
            closeMobileMenu();
        } else {
            mobileMenuTrigger = document.activeElement;
            mobileOverlay.value = true;
            focusMobileMenu();
        }
    } else {
        sidebarOpen.value = !sidebarOpen.value;
    }
};

const closeMobileMenu = ({ restoreFocus = true } = {}) => {
    if (!mobileOverlay.value) return;

    mobileOverlay.value = false;
    if (restoreFocus) {
        nextTick(() => mobileMenuTrigger?.focus?.());
    }
};

const setInitialSidebarState = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value && window.innerWidth < autoCollapseBreakpoint) {
        sidebarOpen.value = false;
    }
};

// Handle resize: close mobile overlay when going to desktop
const handleResize = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value) {
        mobileOverlay.value = false;
    }
};

const handleKeydown = (event) => {
    if (!mobileOverlay.value) return;

    if (event.key === "Escape") {
        event.preventDefault();
        closeMobileMenu();
        return;
    }

    if (event.key !== "Tab") return;

    const focusable = focusableElements();
    if (focusable.length === 0) {
        event.preventDefault();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

onMounted(() => {
    setInitialSidebarState();
    removeNavigateListener = router.on("navigate", () => closeMobileMenu({ restoreFocus: false }));
    window.addEventListener("resize", handleResize);
    window.addEventListener("keydown", handleKeydown);
});
onUnmounted(() => {
    removeNavigateListener?.();
    window.removeEventListener("resize", handleResize);
    window.removeEventListener("keydown", handleKeydown);
});

// ── Breadcrumbs ──────────────────────────────────────────────────────────────
const breadcrumbs = computed(() => {
    if (Array.isArray(page.props.breadcrumbs) && page.props.breadcrumbs.length > 0) {
        return page.props.breadcrumbs;
    }

    const url = page.url.split("?")[0];
    const segments = url.replace(PREFIX, "").split("/").filter(Boolean);

    const crumbs = [{ label: "Beranda", href: PREFIX, icon: "pi-home" }];

    // Map known segments to labels
    const labelMap = {
        users: "Users",
        "access-control": "Access Control",
        "data-contributor-invitations": "Data Contributor Invitations",
        "data-contributor-registration-requests": "Data Contributor Requests",
        create: "Create",
        edit: "Edit",
        moderation: "Moderation",
        pembanding: "Data Pembanding",
        "pembanding-imports": "Bulk Import",
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
            <button
                v-if="mobileOverlay"
                type="button"
                tabindex="-1"
                aria-label="Tutup navigasi aplikasi"
                class="fixed inset-0 bg-black/50 z-40 md:hidden"
                @click="closeMobileMenu"
            />
        </Transition>

        <!-- Sidebar -->
        <AppSidebar
            :sidebarOpen="sidebarOpen" 
            :mobileOverlay="mobileOverlay"
            :inert="sidebarInert"
        />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar Header -->
            <AppTopbar
                :breadcrumbs="breadcrumbs" 
                :user="user" 
                :sidebarExpanded="sidebarExpanded"
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
