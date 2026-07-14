<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useClickOutside } from "../../composables/useClickOutside";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    user: { type: Object, required: true },
    sidebarExpanded: { type: Boolean, required: true },
});

const emit = defineEmits(["toggleSidebar"]);

const page = usePage();
const PREFIX = "/app";

const initials = computed(() => (props.user.name ?? "A").slice(0, 1).toUpperCase());
const canSearch = computed(() => Boolean(page.props.auth?.can?.search));
const globalSearch = ref("");
const notifications = computed(() => page.props.notifications ?? { unread_count: 0, items: [] });
const notificationsOpen = ref(false);

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

// ── Profile Dropdown ─────────────────────────────────────────────────────────
const profileOpen = ref(false);
const profileButton = ref(null);
const profileMenu = ref(null);
const closeProfile = ({ restoreFocus = false } = {}) => {
    profileOpen.value = false;
    if (restoreFocus) nextTick(() => profileButton.value?.focus());
};
const toggleProfile = async () => {
    profileOpen.value = !profileOpen.value;
    if (profileOpen.value) {
        await nextTick();
        profileMenu.value?.querySelector('[role="menuitem"]')?.focus();
    }
};

useClickOutside("[data-profile-dropdown]", () => {
    closeProfile();
}, { enabled: () => profileOpen.value });
useClickOutside("[data-notifications-dropdown]", () => {
    notificationsOpen.value = false;
}, { enabled: () => notificationsOpen.value });

const handleKeydown = (event) => {
    if (profileOpen.value && event.key === "Escape") {
        event.preventDefault();
        closeProfile({ restoreFocus: true });
    } else if (notificationsOpen.value && event.key === "Escape") {
        event.preventDefault();
        notificationsOpen.value = false;
    }
};

onMounted(() => window.addEventListener("keydown", handleKeydown));
onUnmounted(() => window.removeEventListener("keydown", handleKeydown));

const logout = () => router.post("/logout");
const markNotificationsRead = () => router.post("/app/notifications/read-all", {}, {
    preserveScroll: true,
    onSuccess: () => { notificationsOpen.value = false; },
});

const submitGlobalSearch = () => {
    if (!canSearch.value) return;

    const q = globalSearch.value.trim();

    router.get(`${PREFIX}/search`, q ? { q } : {}, {
        preserveState: false,
        replace: false,
    });
};
</script>

<template>
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6 shrink-0">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                @click="emit('toggleSidebar')"
                class="-ml-2 inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                :aria-label="sidebarExpanded ? 'Tutup navigasi aplikasi' : 'Buka navigasi aplikasi'"
                :aria-expanded="sidebarExpanded"
                aria-controls="app-sidebar"
            >
                <i class="pi pi-bars text-base" />
            </button>

            <!-- Breadcrumbs -->
            <nav class="flex min-w-0 items-center text-sm" aria-label="Breadcrumb">
                <template v-for="(crumb, i) in breadcrumbs" :key="i">
                    <span v-if="i > 0" class="mx-1.5 hidden text-slate-300 sm:inline" aria-hidden="true">/</span>
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="min-w-0 items-center gap-1 text-slate-500 transition-colors hover:text-slate-800"
                        :class="i === breadcrumbs.length - 1 ? 'flex' : 'hidden sm:flex'"
                    >
                        <i v-if="crumb.icon" class="pi text-xs" :class="crumb.icon" aria-hidden="true" />
                        <span class="truncate">{{ crumb.label }}</span>
                    </Link>
                    <span v-else class="truncate font-semibold text-slate-800">{{ crumb.label }}</span>
                </template>
            </nav>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <form v-if="canSearch" class="hidden lg:block" role="search" @submit.prevent="submitGlobalSearch">
                <label for="app_global_search" class="sr-only">Pencarian global</label>
                <div class="relative w-72">
                    <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" />
                    <input
                        id="app_global_search"
                        v-model="globalSearch"
                        type="search"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                        placeholder="Cari data aplikasi"
                    />
                </div>
            </form>

            <Link
                v-if="canSearch"
                :href="`${PREFIX}/search`"
                class="inline-flex size-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500 lg:hidden"
                aria-label="Buka pencarian global"
            >
                <i class="pi pi-search text-sm" />
            </Link>

            <div class="relative" data-notifications-dropdown>
                <button
                    type="button"
                    class="relative inline-flex size-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"
                    aria-label="Notifikasi aplikasi"
                    aria-haspopup="menu"
                    :aria-expanded="notificationsOpen"
                    @click="notificationsOpen = !notificationsOpen"
                >
                    <i class="pi pi-bell" aria-hidden="true" />
                    <span v-if="notifications.unread_count" class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                        {{ notifications.unread_count > 99 ? "99+" : notifications.unread_count }}
                    </span>
                </button>
                <Transition name="dropdown">
                    <div v-if="notificationsOpen" role="menu" class="absolute right-0 top-[calc(100%+8px)] z-50 w-[min(340px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-100 bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-bold text-slate-900">Notifikasi</p>
                            <button v-if="notifications.unread_count" type="button" class="text-xs font-bold text-blue-700 hover:underline" @click="markNotificationsRead">Tandai dibaca</button>
                        </div>
                        <div v-if="notifications.items.length" class="divide-y divide-slate-100">
                            <Link v-for="item in notifications.items" :key="item.id" href="/app/export" role="menuitem" class="block min-h-11 px-4 py-3 hover:bg-slate-50">
                                <p class="text-sm font-semibold text-slate-800">{{ item.message }}</p>
                                <p class="mt-1 text-xs uppercase text-slate-500">{{ item.status }}</p>
                            </Link>
                        </div>
                        <p v-else class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada notifikasi baru.</p>
                    </div>
                </Transition>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" data-profile-dropdown>
                <button
                    ref="profileButton"
                    type="button"
                    @click="toggleProfile"
                    class="flex min-h-11 items-center gap-2.5 rounded-full border border-slate-200 bg-white pl-1 pr-3 py-1 text-sm font-medium text-slate-700 transition hover:border-slate-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"
                    aria-haspopup="menu"
                    :aria-expanded="profileOpen"
                    aria-controls="app-profile-menu"
                >
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                        {{ initials }}
                    </span>
                    <span class="hidden sm:block max-w-[150px] truncate text-slate-800 text-xs font-semibold">
                        {{ user.name ?? "Pengguna" }}
                    </span>
                    <i class="pi text-slate-400 text-[10px]" :class="profileOpen ? 'pi-chevron-up' : 'pi-chevron-down'" />
                </button>

                <Transition name="dropdown">
                    <div
                        v-if="profileOpen"
                        id="app-profile-menu"
                        ref="profileMenu"
                        role="menu"
                        class="absolute right-0 top-[calc(100%+8px)] w-48 rounded-xl border border-slate-100 bg-white shadow-lg z-50 overflow-hidden"
                    >
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs text-slate-400">Masuk sebagai</p>
                            <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ user.name }}</p>
                        </div>
                        <div class="p-1">
                            <Link href="/app/profile" role="menuitem" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500">
                                <i class="pi pi-user text-xs" /> Profil
                            </Link>
                            <button
                                type="button"
                                role="menuitem"
                                @click="logout"
                                class="flex min-h-11 w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-500 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-500"
                            >
                                <i class="pi pi-sign-out text-xs" /> Keluar
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
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
