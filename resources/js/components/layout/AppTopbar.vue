<script setup>
import { computed, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useClickOutside } from "../../composables/useClickOutside";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    user: { type: Object, required: true },
    sidebarOpen: { type: Boolean, required: true },
});

const emit = defineEmits(["toggleSidebar"]);

const page = usePage();
const PREFIX = "/app";

const initials = computed(() => (props.user.name ?? "A").slice(0, 1).toUpperCase());
const canSearch = computed(() => (page.props.auth?.permissions ?? []).includes("view_search"));
const globalSearch = ref("");

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
const toggleProfile = () => (profileOpen.value = !profileOpen.value);

useClickOutside("[data-profile-dropdown]", () => {
    profileOpen.value = false;
}, { enabled: () => profileOpen.value });

const logout = () => router.post("/logout");

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
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="emit('toggleSidebar')"
                class="-ml-2 inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                :aria-label="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
                :aria-expanded="sidebarOpen"
            >
                <i class="pi pi-bars text-base" />
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
                        {{ user.name ?? "Pengguna" }}
                    </span>
                    <i class="pi text-slate-400 text-[10px]" :class="profileOpen ? 'pi-chevron-up' : 'pi-chevron-down'" />
                </button>

                <Transition name="dropdown">
                    <div
                        v-if="profileOpen"
                        class="absolute right-0 top-[calc(100%+8px)] w-48 rounded-xl border border-slate-100 bg-white shadow-lg z-50 overflow-hidden"
                    >
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-xs text-slate-400">Masuk sebagai</p>
                            <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ user.name }}</p>
                        </div>
                        <div class="p-1">
                            <Link href="/app/profile" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <i class="pi pi-user text-xs" /> Profil
                            </Link>
                            <button
                                @click="logout"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-500 hover:bg-red-50"
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
