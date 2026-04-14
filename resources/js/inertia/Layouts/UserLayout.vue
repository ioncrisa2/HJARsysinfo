<script setup>
import { computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Divider from "primevue/divider";
import ScrollTop from "primevue/scrolltop";
import Tag from "primevue/tag";

const page = usePage();

const permissions = computed(() => page.props.auth?.permissions ?? []);

const navItems = computed(() =>
    [
        {
            label: "Dashboard",
            href: "/home",
            icon: "pi pi-home",
            permission: "view_any_data::pembanding",
        },
        {
            label: "Data Pembanding",
            href: "/home/pembanding",
            icon: "pi pi-database",
            permission: "view_any_data::pembanding",
        },
        {
            label: "Master Data",
            href: "/home/master-data",
            icon: "pi pi-sitemap",
            permission: "view_any_data::pembanding",
        },
    ].filter((item) => permissions.value.includes(item.permission))
);

const isActive = (href) => page.url === href || page.url.startsWith(`${href}/`);

const logout = () => {
    router.post("/logout");
};
</script>

<template>
    <Head title="Portal User" />

    <div class="min-h-screen bg-slate-100">
        <div class="mx-auto w-full max-w-7xl p-4 lg:p-6">
            <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
                <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h1 class="text-sm font-bold uppercase tracking-wider text-slate-700">Portal User</h1>
                        <Tag value="Internal" severity="contrast" />
                    </div>

                    <Divider />

                    <nav class="flex flex-col gap-2">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                            :class="
                                isActive(item.href)
                                    ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            <i :class="item.icon" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>
                </aside>

                <section class="min-w-0">
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
                    >
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User Area</p>
                            <p class="text-sm font-medium text-slate-800">
                                {{ page.props.auth?.user?.name ?? "User" }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                label="Buka Admin"
                                icon="pi pi-external-link"
                                outlined
                                size="small"
                                @click="() => (window.location.href = '/admin')"
                            />
                            <Button
                                label="Logout"
                                icon="pi pi-sign-out"
                                severity="secondary"
                                text
                                size="small"
                                @click="logout"
                            />
                        </div>
                    </header>

                    <main id="main-content" tabindex="-1" class="pt-4">
                        <slot />
                    </main>
                </section>
            </div>
        </div>

        <ScrollTop :threshold="260" />
    </div>
</template>
