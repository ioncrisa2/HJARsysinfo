<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    sidebarOpen: { type: Boolean, required: true },
    mobileOverlay: { type: Boolean, required: true },
});

const page = usePage();
const PREFIX = "/admin";

const menuSections = computed(() => page.props.adminMenu ?? []);
const homeHref = computed(() => menuSections.value?.[0]?.items?.[0]?.href ?? PREFIX);
const showLabels = computed(() => props.sidebarOpen || props.mobileOverlay);

const isActive = (href) => {
    const url = page.url.split("?")[0];
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
            <div class="h-16 flex items-center justify-center border-b border-slate-800 px-3">
                <Link
                    :href="homeHref"
                    class="flex min-w-0 flex-none items-center gap-3"
                    :title="showLabels ? null : (page.props.appSettings?.company_name || 'Admin Panel')"
                    aria-label="Admin home"
                >
                    <div v-if="page.props.appSettings?.app_logo" class="flex h-8 w-8 items-center justify-center rounded-md overflow-hidden bg-white">
                        <img :src="'/storage/' + page.props.appSettings.app_logo" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500" :style="page.props.appSettings?.primary_color ? { backgroundColor: page.props.appSettings.primary_color } : {}">
                        <i class="pi pi-compass text-white text-sm" />
                    </div>
                    <span
                        v-if="showLabels"
                        class="text-sm font-bold tracking-tight text-white whitespace-nowrap overflow-hidden"
                    >
                        {{ page.props.appSettings?.company_name || 'Admin Panel' }}<span :style="page.props.appSettings?.primary_color ? { color: page.props.appSettings.primary_color } : {}" class="text-amber-500">.</span>
                    </span>
                </Link>
            </div>

            <nav
                :class="[
                    'flex-1 overflow-y-auto py-6',
                    showLabels ? 'px-3' : 'px-2',
                ]"
            >
                <div
                    v-for="section in menuSections"
                    :key="section.label"
                    :class="showLabels ? 'mb-5 last:mb-0' : 'mb-3 last:mb-0'"
                >
                    <p
                        v-if="showLabels"
                        class="mb-2 px-3 text-[11px] font-bold uppercase text-slate-500"
                    >
                        {{ section.label }}
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center rounded-lg px-3 py-3 text-sm font-medium transition-all duration-200"
                            :class="[
                                showLabels ? 'justify-start gap-3' : 'justify-center',
                                isActive(item.href)
                                    ? 'bg-amber-500/10 text-amber-400 ring-1 ring-amber-400/10'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                            ]"
                            :title="showLabels ? null : item.label"
                            :aria-label="showLabels ? null : item.label"
                        >
                            <i class="pi" :class="[item.icon, showLabels ? 'text-base' : 'text-lg']" />
                            <span v-if="showLabels" class="whitespace-nowrap">{{ item.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </div>
    </aside>
</template>
