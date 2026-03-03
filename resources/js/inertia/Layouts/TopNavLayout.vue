<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import ScrollTop from "primevue/scrolltop";

const page = usePage();

const user = computed(() => page.props.auth?.user ?? {});
const initials = computed(() => (user.value.name ?? "U").slice(0, 1).toUpperCase());

const menuItems = [
    { label: "Dashboard", href: "/home" },
    { label: "Bank Data", href: "/home/pembanding" },
    { label: "Master Data", href: "/home/master-data" },
];

const isActive = (href) => {
    if (href === "/home") return page.url === "/home";
    return page.url === href || page.url.startsWith(`${href}/`);
};

const profileOpen = ref(false);
const mobileMenuOpen = ref(false);

const toggleProfile = () => (profileOpen.value = !profileOpen.value);
const closeProfile = () => (profileOpen.value = false);
const toggleMobileMenu = () => (mobileMenuOpen.value = !mobileMenuOpen.value);
const closeMobileMenu = () => (mobileMenuOpen.value = false);
const logout = () => router.post("/logout");

const handleEscape = (event) => {
    if (event.key === "Escape") {
        closeProfile();
        closeMobileMenu();
    }
};

const handleClickOutside = (event) => {
    if (!event.target.closest(".profile-menu")) closeProfile();
    if (!event.target.closest(".mobile-menu-wrapper")) closeMobileMenu();
};

onMounted(() => {
    window.addEventListener("keydown", handleEscape);
    window.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleEscape);
    window.removeEventListener("click", handleClickOutside);
});
</script>

<template>

    <Head title="Bank Data KJPP HJA'R" />

    <div class="flex min-h-screen flex-col bg-slate-50">

        <Toast />

        <!-- Navbar -->
        <header class="sticky top-0 z-50 bg-white border-b border-slate-100 shadow-[0_1px_3px_rgba(0,0,0,0.06)]">
            <div class="mx-auto h-[60px] w-full max-w-7xl px-6 flex items-center justify-between gap-4">

                <!-- Logo -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-900">
                        <i class="pi pi-compass text-white text-xs" />
                    </div>
                    <span class="text-sm font-bold tracking-tight text-slate-900">
                        Bank Data KJPP HJA'R<span class="text-amber-500">.</span>
                    </span>
                </div>

                <!-- Desktop Nav Links (hidden on mobile) -->
                <nav class="hidden md:flex items-center justify-center gap-1 flex-1">
                    <Link
                        v-for="item in menuItems"
                        :key="item.href"
                        :href="item.href"
                        class="relative px-4 py-2 text-sm font-medium transition-colors rounded-lg"
                        :class="isActive(item.href)
                            ? 'text-slate-900 bg-slate-100'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        {{ item.label }}
                        <span
                            v-if="isActive(item.href)"
                            class="absolute bottom-1 left-1/2 -translate-x-1/2 h-1 w-1 rounded-full bg-amber-500"
                        />
                    </Link>
                </nav>

                <!-- Right side: User menu (desktop) + Burger (mobile) -->
                <div class="flex items-center gap-3">

                    <!-- Desktop User Menu -->
                    <div class="relative hidden md:flex justify-end profile-menu">
                        <button
                            type="button"
                            class="flex items-center gap-2.5 rounded-full border border-slate-200 bg-white pl-1 pr-3 py-1 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:shadow-sm"
                            @click.stop="toggleProfile"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">
                                {{ initials }}
                            </span>
                            <span class="max-w-[100px] truncate text-slate-800 text-xs font-semibold">{{ user.name ?? "Pengguna" }}</span>
                            <i class="pi text-slate-400 text-[10px]" :class="profileOpen ? 'pi-chevron-up' : 'pi-chevron-down'" />
                        </button>

                        <!-- Dropdown -->
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 translate-y-1 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 translate-y-0 scale-100"
                            leave-to-class="opacity-0 translate-y-1 scale-95"
                        >
                            <div
                                v-if="profileOpen"
                                class="absolute right-0 top-[calc(100%+8px)] w-52 origin-top-right overflow-hidden rounded-xl border border-slate-100 bg-white shadow-lg shadow-slate-200/80"
                            >
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-xs text-slate-400">Masuk sebagai</p>
                                    <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ user.name ?? "Pengguna" }}</p>
                                </div>
                                <div class="p-1">
                                    <Link
                                        href="/profile"
                                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                                        @click="closeProfile"
                                    >
                                        <i class="pi pi-user text-slate-400 text-xs" />
                                        Profil Saya
                                    </Link>
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-500 transition hover:bg-red-50"
                                        @click="logout"
                                    >
                                        <i class="pi pi-sign-out text-red-400 text-xs" />
                                        Keluar
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Mobile Burger Button -->
                    <div class="mobile-menu-wrapper md:hidden">
                        <button
                            type="button"
                            class="flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                            @click.stop="toggleMobileMenu"
                            aria-label="Toggle menu"
                        >
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 rotate-90 scale-75"
                                enter-to-class="opacity-100 rotate-0 scale-100"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 rotate-0 scale-100"
                                leave-to-class="opacity-0 rotate-90 scale-75"
                                mode="out-in"
                            >
                                <i v-if="mobileMenuOpen" key="close" class="pi pi-times text-sm" />
                                <i v-else key="burger" class="pi pi-bars text-sm" />
                            </Transition>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="mobile-menu-wrapper md:hidden border-t border-slate-100 bg-white px-4 pb-4 pt-2 shadow-lg"
                >
                    <!-- User info -->
                    <div class="flex items-center gap-3 py-3 mb-2 border-b border-slate-100">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white flex-shrink-0">
                            {{ initials }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 leading-tight">{{ user.name ?? "Pengguna" }}</p>
                            <p class="text-xs text-slate-400">Pengguna Aktif</p>
                        </div>
                    </div>

                    <!-- Nav Links -->
                    <nav class="flex flex-col gap-1 mb-3">
                        <Link
                            v-for="item in menuItems"
                            :key="item.href"
                            :href="item.href"
                            class="relative flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors"
                            :class="isActive(item.href)
                                ? 'text-slate-900 bg-slate-100'
                                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
                            @click="closeMobileMenu"
                        >
                            <span
                                v-if="isActive(item.href)"
                                class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"
                            />
                            <span v-else class="w-1.5 h-1.5 flex-shrink-0" />
                            {{ item.label }}
                        </Link>
                    </nav>

                    <!-- Actions -->
                    <div class="flex flex-col gap-1 pt-2 border-t border-slate-100">
                        <Link
                            href="/profile"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50"
                            @click="closeMobileMenu"
                        >
                            <i class="pi pi-user text-slate-400 text-xs" />
                            Profil Saya
                        </Link>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-red-500 transition hover:bg-red-50"
                            @click="logout"
                        >
                            <i class="pi pi-sign-out text-red-400 text-xs" />
                            Keluar
                        </button>
                    </div>
                </div>
            </Transition>
        </header>

        <!-- Page content -->
        <main class="mx-auto w-full max-w-7xl px-4 md:px-6 py-6">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-100 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-6 py-4 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <div class="flex h-5 w-5 items-center justify-center rounded bg-slate-900">
                        <i class="pi pi-compass text-white" style="font-size: 9px" />
                    </div>
                    <span class="font-medium text-slate-500">
                        Bank Data KJPP HJA'R<span class="text-amber-500">.</span>
                    </span>
                </div>
                <span class="hidden sm:block">© {{ new Date().getFullYear() }} Bank Data KJPP HJA'R User. All rights reserved.</span>
                <div class="flex items-center gap-4">
                    <Link href="/profile" class="hover:text-slate-600 transition-colors">Profil</Link>
                    <button type="button" class="hover:text-red-500 transition-colors" @click="logout">Keluar</button>
                </div>
            </div>
        </footer>

        <ScrollTop :threshold="260" />

    </div>
</template>
