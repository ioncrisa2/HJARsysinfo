<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";

// ── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    title: { type: String, default: "Admin Control Panel" },
});

// ── Page & Auth ──────────────────────────────────────────────────────────────
const page = usePage();
const toast = useToast();
const PREFIX = "/admin";
const user = computed(() => page.props.auth?.user ?? {});
const initials = computed(() => (user.value.name ?? "A").slice(0, 1).toUpperCase());
const globalSearch = ref("");

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

watch(
    () => page.url,
    (url) => {
        if (!url.startsWith(`${PREFIX}/search`)) {
            return;
        }

        const query = new URLSearchParams(url.split("?")[1] ?? "");
        globalSearch.value = query.get("q") ?? "";
    },
    { immediate: true },
);

// ── Menu ─────────────────────────────────────────────────────────────────────
const menuSections = [
    {
        label: "Overview",
        items: [
            { label: "Dashboard", href: `${PREFIX}`, icon: "pi-home" },
        ],
    },
    {
        label: "User Management",
        items: [
            { label: "Users", href: `${PREFIX}/users`, icon: "pi-users" },
            { label: "Access Control", href: `${PREFIX}/access-control`, icon: "pi-key" },
        ],
    },
    {
        label: "Data Operations",
        items: [
            { label: "Moderation Desk", href: `${PREFIX}/moderation`, icon: "pi-shield" },
            { label: "Appraisal Data", href: `${PREFIX}/pembanding`, icon: "pi-database" },
            { label: "Master Data", href: `${PREFIX}/master-data`, icon: "pi-box" },
            { label: "Geo Data", href: `${PREFIX}/geo`, icon: "pi-map" },
        ],
    },
    {
        label: "System",
        items: [
            { label: "Export Data", href: `${PREFIX}/export`, icon: "pi-download" },
            { label: "System Backup", href: `${PREFIX}/backup`, icon: "pi-archive" },
            { label: "Settings", href: `${PREFIX}/settings`, icon: "pi-cog" },
            { label: "Activity Logs", href: `${PREFIX}/activity-logs`, icon: "pi-list" },
            { label: "Search", href: `${PREFIX}/search`, icon: "pi-search" },
        ],
    },
];

const isActive = (href) => {
    const url = page.url.split("?")[0]; // strip query string
    if (href === PREFIX) return url === PREFIX || url === `${PREFIX}/`;
    return url === href || url.startsWith(`${href}/`);
};

// ── Sidebar State ────────────────────────────────────────────────────────────
const sidebarOpen = ref(true);
const mobileOverlay = ref(false);

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

onMounted(() => window.addEventListener("resize", handleResize));
onUnmounted(() => window.removeEventListener("resize", handleResize));

// ── Profile Dropdown ─────────────────────────────────────────────────────────
const profileOpen = ref(false);
const toggleProfile = () => (profileOpen.value = !profileOpen.value);

// Close profile dropdown when clicking outside
const closeProfile = (e) => {
    if (profileOpen.value && !e.target.closest("[data-profile-dropdown]")) {
        profileOpen.value = false;
    }
};
onMounted(() => document.addEventListener("click", closeProfile));
onUnmounted(() => document.removeEventListener("click", closeProfile));

const logout = () => router.post("/logout");

const submitGlobalSearch = () => {
    const q = globalSearch.value.trim();

    router.get(`${PREFIX}/search`, q ? { q } : {}, {
        preserveState: false,
        replace: false,
    });
};

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
        <aside
            :class="[
                'bg-slate-900 flex-shrink-0 transition-all duration-300 ease-in-out z-50',
                // Mobile: fixed overlay
                'fixed md:relative inset-y-0 left-0',
                // Mobile: slide in/out
                mobileOverlay ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0',
                // Desktop: toggle width
                sidebarOpen ? 'md:w-64' : 'md:w-20',
            ]"
        >
            <div class="h-full flex flex-col">
                <!-- Branding -->
                <div class="h-16 flex items-center justify-center border-b border-slate-800">
                    <Link :href="PREFIX" class="flex items-center gap-3">
                        <div v-if="page.props.appSettings?.app_logo" class="flex h-8 w-8 items-center justify-center rounded-md overflow-hidden bg-white">
                            <img :src="'/storage/' + page.props.appSettings.app_logo" class="h-full w-full object-cover" />
                        </div>
                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500" :style="page.props.appSettings?.primary_color ? { backgroundColor: page.props.appSettings.primary_color } : {}">
                            <i class="pi pi-compass text-white text-sm" />
                        </div>
                        <span
                            v-if="sidebarOpen || mobileOverlay"
                            class="text-sm font-bold tracking-tight text-white whitespace-nowrap overflow-hidden"
                        >
                            {{ page.props.appSettings?.company_name || 'Admin Panel' }}<span :style="page.props.appSettings?.primary_color ? { color: page.props.appSettings.primary_color } : {}" class="text-amber-500">.</span>
                        </span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto px-3 py-6">
                    <div v-for="section in menuSections" :key="section.label" class="mb-5 last:mb-0">
                        <p
                            v-if="sidebarOpen || mobileOverlay"
                            class="mb-2 px-3 text-[11px] font-bold uppercase text-slate-500"
                        >
                            {{ section.label }}
                        </p>
                        <div class="space-y-1">
                            <Link
                                v-for="item in section.items"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-amber-500/10 text-amber-400'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
                                "
                            >
                                <i class="pi text-base" :class="item.icon" />
                                <span v-if="sidebarOpen || mobileOverlay" class="whitespace-nowrap">{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- Sidebar Footer Toggle (Desktop only) -->
                <div class="hidden md:flex p-4 border-t border-slate-800 justify-center">
                    <button @click="toggleSidebar" class="p-2 rounded-md hover:bg-slate-800 text-slate-400 transition-colors">
                        <i class="pi" :class="sidebarOpen ? 'pi-angle-double-left' : 'pi-angle-double-right'" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6 shrink-0">
                <div class="flex items-center gap-3">
                    <!-- Mobile hamburger -->
                    <button
                        @click="toggleSidebar"
                        class="md:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <i class="pi pi-bars text-lg" />
                    </button>

                    <!-- Breadcrumbs -->
                    <nav class="flex items-center text-sm">
                        <template v-for="(crumb, i) in breadcrumbs" :key="i">
                            <span v-if="i > 0" class="text-slate-300 mx-1.5">/</span>
                            <Link
                                v-if="crumb.href"
                                :href="crumb.href"
                                class="text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1"
                            >
                                <i v-if="crumb.icon" class="pi text-xs" :class="crumb.icon" />
                                <span>{{ crumb.label }}</span>
                            </Link>
                            <span v-else class="text-slate-800 font-semibold">{{ crumb.label }}</span>
                        </template>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <form class="hidden lg:block" role="search" @submit.prevent="submitGlobalSearch">
                        <label for="admin_global_search" class="sr-only">Global search</label>
                        <div class="relative w-72">
                            <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                            <input
                                id="admin_global_search"
                                v-model="globalSearch"
                                type="search"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                placeholder="Search admin data"
                            />
                        </div>
                    </form>

                    <Link
                        :href="`${PREFIX}/search`"
                        class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-900 lg:hidden"
                        aria-label="Open global search"
                    >
                        <i class="pi pi-search text-sm" />
                    </Link>

                    <!-- Profile Dropdown -->
                    <div class="relative" data-profile-dropdown>
                    <button
                        @click="toggleProfile"
                        class="flex items-center gap-2.5 rounded-full border border-slate-200 bg-white pl-1 pr-3 py-1 text-sm font-medium text-slate-700 transition hover:border-slate-300"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                            {{ initials }}
                        </span>
                        <span class="hidden sm:block max-w-[150px] truncate text-slate-800 text-xs font-semibold">
                            {{ user.name ?? "Administrator" }}
                        </span>
                        <i class="pi text-slate-400 text-[10px]" :class="profileOpen ? 'pi-chevron-up' : 'pi-chevron-down'" />
                    </button>

                    <Transition name="dropdown">
                        <div
                            v-if="profileOpen"
                            class="absolute right-0 top-[calc(100%+8px)] w-48 rounded-xl border border-slate-100 bg-white shadow-lg z-50 overflow-hidden"
                        >
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="text-xs text-slate-400">Signed in as</p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ user.name }}</p>
                            </div>
                            <div class="p-1">
                                <Link href="/admin/profile" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <i class="pi pi-user text-xs" /> Profile
                                </Link>
                                <Link href="/home" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <i class="pi pi-home text-xs" /> User Panel
                                </Link>
                                <button
                                    @click="logout"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-500 hover:bg-red-50"
                                >
                                    <i class="pi pi-sign-out text-xs" /> Logout
                                </button>
                            </div>
                        </div>
                    </Transition>
                    </div>
                </div>
            </header>

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

/* Dropdown transition for profile menu */
.dropdown-enter-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-leave-active {
    transition: opacity 0.1s ease, transform 0.1s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.97);
}
</style>
