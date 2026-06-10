<script setup>
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    sidebarOpen: { type: Boolean, required: true },
    mobileOverlay: { type: Boolean, required: true },
});

const emit = defineEmits(["toggleSidebar"]);

const page = usePage();
const PREFIX = "/admin";

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
</script>

<template>
    <aside
        :class="[
            'bg-slate-900 flex-shrink-0 transition-all duration-300 ease-in-out z-50',
            'fixed md:relative inset-y-0 left-0',
            mobileOverlay ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0',
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
                <button @click="emit('toggleSidebar')" class="p-2 rounded-md hover:bg-slate-800 text-slate-400 transition-colors">
                    <i class="pi" :class="sidebarOpen ? 'pi-angle-double-left' : 'pi-angle-double-right'" />
                </button>
            </div>
        </div>
    </aside>
</template>
