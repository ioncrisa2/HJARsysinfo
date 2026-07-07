<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import ScrollTop from "primevue/scrolltop";
import { useClickOutside } from "../composables/useClickOutside";

const page = usePage();

const user = computed(() => page.props.auth?.user ?? {});
const initials = computed(() => (user.value.name ?? "U").slice(0, 1).toUpperCase());
const permissions = computed(() => page.props.auth?.permissions ?? []);
const canBulkImport = computed(() => Boolean(page.props.auth?.can_bulk_import));

const canViewData = computed(() => permissions.value.includes("view_any_data::pembanding"));
const bankDataChildren = computed(() => [
    canViewData.value ? { label: "List Data", href: "/home/pembanding", icon: "pi-list" } : null,
    canBulkImport.value ? { label: "Bulk Import", href: "/home/pembanding-imports", icon: "pi-file-import" } : null,
].filter(Boolean));
const dashboardItem = computed(() => permissions.value.some((permission) => ["view_map", "view_any_data::pembanding"].includes(permission))
    ? { label: "Dashboard", href: "/home", icon: "pi-home" }
    : null);
const masterDataItem = computed(() => permissions.value.some((permission) => ["view_master_data", "view_geo_data"].includes(permission))
    ? { label: "Master Data", href: "/home/master-data", icon: "pi-database" }
    : null);

const isActive = (href) => {
    if (href === "/home") return page.url === "/home";
    return page.url === href || page.url.startsWith(`${href}/`);
};

const profileOpen = ref(false);
const mobileMenuOpen = ref(false);
const bankDataOpen = ref(false);

const profileButtonRef = ref(null);
const mobileMenuButtonRef = ref(null);
const bankDataButtonRef = ref(null);
const profileMenuRef = ref(null);
const mobileMenuRef = ref(null);
const bankDataMenuRef = ref(null);

const toggleProfile = () => (profileOpen.value = !profileOpen.value);
const closeProfile = () => (profileOpen.value = false);
const toggleMobileMenu = () => (mobileMenuOpen.value = !mobileMenuOpen.value);
const closeMobileMenu = () => (mobileMenuOpen.value = false);
const toggleBankData = () => (bankDataOpen.value = !bankDataOpen.value);
const closeBankData = () => (bankDataOpen.value = false);
const logout = () => router.post("/logout");

const focusFirstIn = (el) => {
    if (!el) return;
    const target = el.querySelector(
        'a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])'
    );
    target?.focus?.();
};

const openAndFocus = async () => {
    await nextTick();
    if (profileOpen.value) focusFirstIn(profileMenuRef.value);
    if (mobileMenuOpen.value) focusFirstIn(mobileMenuRef.value);
    if (bankDataOpen.value) focusFirstIn(bankDataMenuRef.value);
};

const handleEscape = async (event) => {
    if (event.key === "Escape") {
        const wasProfileOpen = profileOpen.value;
        const wasMobileOpen = mobileMenuOpen.value;
        const wasBankDataOpen = bankDataOpen.value;

        closeProfile();
        closeMobileMenu();
        closeBankData();

        await nextTick();
        if (wasProfileOpen) profileButtonRef.value?.focus?.();
        if (wasMobileOpen) mobileMenuButtonRef.value?.focus?.();
        if (wasBankDataOpen) bankDataButtonRef.value?.focus?.();
    }
};

useClickOutside(".profile-menu", closeProfile);
useClickOutside(".mobile-menu-wrapper", closeMobileMenu);
useClickOutside(".bank-data-menu", closeBankData);

onMounted(() => {
    window.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleEscape);
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
                    <div class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-slate-200">
                        <img :src="'/images/h-logo.jpg'" alt="" class="h-full w-full object-cover" aria-hidden="true" />
                    </div>
                    <span class="text-sm font-bold tracking-tight text-slate-900">
                        Bank Data KJPP HJA'R<span class="text-amber-500">.</span>
                    </span>
                </div>

                <!-- Desktop Nav Links (hidden on mobile) -->
                <nav class="hidden md:flex items-center justify-center gap-1 flex-1">
                    <Link
                        v-if="dashboardItem"
                        :href="dashboardItem.href"
                        class="relative inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                        :class="isActive(dashboardItem.href)
                            ? 'text-slate-900 bg-slate-100'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        <i class="pi text-xs" :class="dashboardItem.icon" aria-hidden="true" />
                        {{ dashboardItem.label }}
                        <span
                            v-if="isActive(dashboardItem.href)"
                            class="absolute bottom-1 left-1/2 -translate-x-1/2 h-1 w-1 rounded-full bg-amber-500"
                        />
                    </Link>

                    <div v-if="bankDataChildren.length" class="bank-data-menu relative">
                        <button
                            ref="bankDataButtonRef"
                            type="button"
                            class="relative inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            :class="bankDataChildren.some((item) => isActive(item.href))
                                ? 'bg-slate-100 text-slate-900'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
                            :aria-expanded="bankDataOpen ? 'true' : 'false'"
                            aria-controls="bank-data-menu"
                            @click.stop="() => { toggleBankData(); openAndFocus(); }"
                            @keydown.down.prevent="() => { bankDataOpen = true; openAndFocus(); }"
                        >
                            <i class="pi pi-folder text-xs" aria-hidden="true" />
                            Bank Data
                            <i class="pi text-[9px] text-slate-400" :class="bankDataOpen ? 'pi-chevron-up' : 'pi-chevron-down'" aria-hidden="true" />
                            <span
                                v-if="bankDataChildren.some((item) => isActive(item.href))"
                                class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-amber-500"
                            />
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="translate-y-1 scale-95 opacity-0"
                            enter-to-class="translate-y-0 scale-100 opacity-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="translate-y-0 scale-100 opacity-100"
                            leave-to-class="translate-y-1 scale-95 opacity-0"
                        >
                            <div
                                v-if="bankDataOpen"
                                id="bank-data-menu"
                                ref="bankDataMenuRef"
                                class="absolute left-1/2 top-[calc(100%+8px)] w-52 -translate-x-1/2 rounded-xl border border-slate-100 bg-white p-1.5 shadow-lg shadow-slate-200/80"
                            >
                                <Link
                                    v-for="item in bankDataChildren"
                                    :key="item.href"
                                    :href="item.href"
                                    class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"
                                    :class="isActive(item.href) ? 'bg-amber-50 text-amber-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                                    @click="closeBankData"
                                >
                                    <i class="pi w-4 text-center text-xs" :class="item.icon" aria-hidden="true" />
                                    {{ item.label }}
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <Link
                        v-if="masterDataItem"
                        :href="masterDataItem.href"
                        class="relative inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                        :class="isActive(masterDataItem.href)
                            ? 'text-slate-900 bg-slate-100'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        <i class="pi text-xs" :class="masterDataItem.icon" aria-hidden="true" />
                        {{ masterDataItem.label }}
                        <span
                            v-if="isActive(masterDataItem.href)"
                            class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-amber-500"
                        />
                    </Link>
                </nav>

                <!-- Right side: User menu (desktop) + Burger (mobile) -->
                <div class="flex items-center gap-3">

                    <!-- Desktop User Menu -->
                    <div class="relative hidden md:flex justify-end profile-menu">
                        <button
                            ref="profileButtonRef"
                            type="button"
                            class="flex items-center gap-2.5 rounded-full border border-slate-200 bg-white pl-1 pr-3 py-1 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:shadow-sm"
                            :aria-expanded="profileOpen ? 'true' : 'false'"
                            aria-controls="profile-menu"
                            @click.stop="() => { toggleProfile(); openAndFocus(); }"
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
                                id="profile-menu"
                                ref="profileMenuRef"
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
                            ref="mobileMenuButtonRef"
                            type="button"
                            class="flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                            :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                            aria-controls="mobile-menu"
                            aria-label="Toggle menu"
                            @click.stop="() => { toggleMobileMenu(); openAndFocus(); }"
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
                    id="mobile-menu"
                    ref="mobileMenuRef"
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
                            v-if="dashboardItem"
                            :href="dashboardItem.href"
                            class="relative flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors"
                            :class="isActive(dashboardItem.href)
                                ? 'text-slate-900 bg-slate-100'
                                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
                            @click="closeMobileMenu"
                        >
                            <i class="pi w-5 text-center text-sm" :class="dashboardItem.icon" aria-hidden="true" />
                            {{ dashboardItem.label }}
                        </Link>

                        <div v-if="bankDataChildren.length" class="rounded-lg border border-slate-100 bg-slate-50/70 p-1.5">
                            <div class="flex min-h-10 items-center gap-3 px-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <i class="pi pi-folder w-5 text-center text-sm" aria-hidden="true" />
                                Bank Data
                            </div>
                            <Link
                                v-for="item in bankDataChildren"
                                :key="item.href"
                                :href="item.href"
                                class="flex min-h-11 items-center gap-3 rounded-lg px-3 pl-7 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500"
                                :class="isActive(item.href) ? 'bg-white text-amber-900 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                                @click="closeMobileMenu"
                            >
                                <i class="pi w-5 text-center text-sm" :class="item.icon" aria-hidden="true" />
                                {{ item.label }}
                            </Link>
                        </div>

                        <Link
                            v-if="masterDataItem"
                            :href="masterDataItem.href"
                            class="relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="isActive(masterDataItem.href)
                                ? 'bg-slate-100 text-slate-900'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
                            @click="closeMobileMenu"
                        >
                            <i class="pi w-5 text-center text-sm" :class="masterDataItem.icon" aria-hidden="true" />
                            {{ masterDataItem.label }}
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
        <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-7xl px-4 md:px-6 py-6">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-100 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-6 py-4 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <div class="flex h-5 w-5 items-center justify-center overflow-hidden rounded bg-white ring-1 ring-slate-200">
                        <img :src="'/images/h-logo.jpg'" alt="" class="h-full w-full object-cover" aria-hidden="true" />
                    </div>
                    <span class="font-medium text-slate-500">
                        Bank Data KJPP HJA'R<span class="text-amber-500">.</span>
                    </span>
                </div>
                <span class="hidden sm:block">(c) {{ new Date().getFullYear() }} Bank Data KJPP HJA'R User. All rights reserved.</span>
                <div class="flex items-center gap-4">
                    <Link href="/profile" class="hover:text-slate-600 transition-colors">Profil</Link>
                    <button type="button" class="hover:text-red-500 transition-colors" @click="logout">Keluar</button>
                </div>
            </div>
        </footer>

        <ScrollTop :threshold="260" />

    </div>
</template>
