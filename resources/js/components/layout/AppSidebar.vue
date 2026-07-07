<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    sidebarOpen: { type: Boolean, required: true },
    mobileOverlay: { type: Boolean, required: true },
});

const page = usePage();
const PREFIX = "/app";

const menuSections = computed(() => page.props.appMenu ?? []);
const homeHref = computed(() => menuSections.value?.[0]?.items?.[0]?.href ?? PREFIX);
const showLabels = computed(() => props.sidebarOpen || props.mobileOverlay);
const expandedGroups = ref({});

const isActive = (href) => {
    const url = page.url.split("?")[0];
    if (href === PREFIX) return url === PREFIX || url === `${PREFIX}/`;
    return url === href || url.startsWith(`${href}/`);
};

const isGroupActive = (item) => item.children?.some((child) => isActive(child.href)) ?? false;
const groupKey = (section, item) => `${section.label}-${item.label}`;
const groupId = (section, item) => `app-menu-${groupKey(section, item).toLowerCase().replace(/[^a-z0-9]+/g, "-")}`;
const isGroupExpanded = (section, item) => expandedGroups.value[groupKey(section, item)] ?? isGroupActive(item);
const toggleGroup = (section, item) => {
    const key = groupKey(section, item);
    expandedGroups.value = { ...expandedGroups.value, [key]: !isGroupExpanded(section, item) };
};
</script>

<template>
    <aside
        aria-label="Navigasi aplikasi"
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
                    :title="showLabels ? null : (page.props.appSettings?.company_name || 'Bank Data KJPP HJA\'R')"
                    aria-label="Beranda aplikasi"
                >
                    <div v-if="page.props.appSettings?.app_logo" class="flex h-8 w-8 items-center justify-center rounded-md overflow-hidden bg-white">
                        <img :src="'/storage/' + page.props.appSettings.app_logo" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="flex h-8 w-8 items-center justify-center rounded-md overflow-hidden bg-white ring-1 ring-slate-700">
                        <img :src="'/images/h-logo.jpg'" alt="" class="h-full w-full object-cover" aria-hidden="true" />
                    </div>
                    <span
                        v-if="showLabels"
                        class="text-sm font-bold tracking-tight text-white whitespace-nowrap overflow-hidden"
                    >
                        {{ page.props.appSettings?.company_name || "Bank Data KJPP HJA'R" }}<span :style="page.props.appSettings?.primary_color ? { color: page.props.appSettings.primary_color } : {}" class="text-amber-500">.</span>
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
                        <template v-for="item in section.items" :key="item.href ?? item.label">
                            <div v-if="item.children?.length">
                                <button
                                    v-if="showLabels"
                                    type="button"
                                    class="flex w-full items-center justify-start gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"
                                    :class="isGroupActive(item)
                                        ? 'bg-amber-500/10 text-amber-400 ring-1 ring-amber-400/10'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                    :aria-expanded="isGroupExpanded(section, item) ? 'true' : 'false'"
                                    :aria-controls="groupId(section, item)"
                                    @click="toggleGroup(section, item)"
                                >
                                    <i class="pi text-base" :class="item.icon" aria-hidden="true" />
                                    <span class="flex-1 whitespace-nowrap text-left">{{ item.label }}</span>
                                    <i class="pi text-[10px]" :class="isGroupExpanded(section, item) ? 'pi-chevron-up' : 'pi-chevron-down'" aria-hidden="true" />
                                </button>

                                <div
                                    v-if="showLabels && isGroupExpanded(section, item)"
                                    :id="groupId(section, item)"
                                    class="ml-5 mt-1 space-y-1 border-l border-slate-700 pl-2"
                                >
                                    <Link
                                        v-for="child in item.children"
                                        :key="child.href"
                                        :href="child.href"
                                        class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"
                                        :class="isActive(child.href)
                                            ? 'bg-amber-500/10 text-amber-400'
                                            : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                    >
                                        <i class="pi w-4 text-center text-sm" :class="child.icon" aria-hidden="true" />
                                        <span class="whitespace-nowrap">{{ child.label }}</span>
                                    </Link>
                                </div>

                                <div v-else-if="!showLabels" class="space-y-1">
                                    <Link
                                        v-for="child in item.children"
                                        :key="child.href"
                                        :href="child.href"
                                        class="flex items-center justify-center rounded-lg px-3 py-3 text-slate-400 transition-all duration-200 hover:bg-slate-800 hover:text-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"
                                        :class="isActive(child.href) ? 'bg-amber-500/10 text-amber-400 ring-1 ring-amber-400/10' : ''"
                                        :title="child.label"
                                        :aria-label="child.label"
                                    >
                                        <i class="pi text-lg" :class="child.icon" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>

                            <Link
                                v-else
                                :href="item.href"
                                class="flex items-center rounded-lg px-3 py-3 text-sm font-medium transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"
                                :class="[
                                    showLabels ? 'justify-start gap-3' : 'justify-center',
                                    isActive(item.href)
                                        ? 'bg-amber-500/10 text-amber-400 ring-1 ring-amber-400/10'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                                ]"
                                :title="showLabels ? null : item.label"
                                :aria-label="showLabels ? null : item.label"
                            >
                                <i class="pi" :class="[item.icon, showLabels ? 'text-base' : 'text-lg']" aria-hidden="true" />
                                <span v-if="showLabels" class="whitespace-nowrap">{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </nav>
        </div>
    </aside>
</template>
