<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import UiButton from "../../components/ui/UiButton.vue";
import UiField from "../../components/ui/UiField.vue";
import UiSurface from "../../components/ui/UiSurface.vue";

const page = usePage();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Login - Bank Data KJPP HJA'R" />

    <main class="relative min-h-[100svh] bg-slate-50">
        <!-- Subtle background: grid + soft gradient (kept neutral) -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(245,158,11,0.12),transparent_42%),radial-gradient(circle_at_70%_70%,rgba(15,23,42,0.06),transparent_55%)]" />
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.35]"
            style="background-image: linear-gradient(to right, rgba(15,23,42,0.06) 1px, transparent 1px), linear-gradient(to bottom, rgba(15,23,42,0.06) 1px, transparent 1px); background-size: 40px 40px;"
        />

        <div class="relative mx-auto flex min-h-[100svh] w-full max-w-7xl items-center justify-center px-4 py-10">
            <UiSurface class="w-full max-w-md" padding="lg">
                <header class="mb-7 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-[var(--radius-sm)] bg-slate-900 text-white shadow-[var(--shadow-surface)]">
                        <i class="pi pi-compass text-sm" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold tracking-tight text-slate-900">
                            Bank Data KJPP HJA'R<span class="text-amber-600">.</span>
                        </p>
                        <p class="truncate text-xs text-slate-500">Masuk untuk melanjutkan</p>
                    </div>
                </header>

                <div
                    v-if="page.props.flash?.error"
                    class="mb-5 rounded-[var(--radius-md)] border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
                >
                    {{ page.props.flash.error }}
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <UiField id="email" label="Email" :error="form.errors.email">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            class="w-full rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 py-2 text-[16px] text-slate-900 placeholder:text-slate-400 shadow-sm transition focus-visible:border-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--focus-ring)]"
                            placeholder="nama@perusahaan.com"
                        />
                    </UiField>

                    <UiField id="password" label="Password" :error="form.errors.password">
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-[var(--radius-sm)] border border-slate-200 bg-white px-3 py-2 text-[16px] text-slate-900 placeholder:text-slate-400 shadow-sm transition focus-visible:border-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--focus-ring)]"
                            placeholder="********"
                        />
                    </UiField>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--focus-ring)]"
                        />
                        Ingat saya
                    </label>

                    <div class="pt-2">
                        <UiButton type="submit" variant="primary" class="w-full" :loading="form.processing">
                            Masuk
                        </UiButton>
                    </div>

                    <div
                        v-if="form.hasErrors && !form.errors.email && !form.errors.password"
                        class="rounded-[var(--radius-md)] border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                    >
                        Login gagal. Silakan periksa email dan password Anda.
                    </div>
                </form>

                <footer class="mt-8 flex items-center justify-between text-xs text-slate-500">
                    <span class="ui-tabular">v1</span>
                    <span>(c) {{ new Date().getFullYear() }}</span>
                </footer>
            </UiSurface>
        </div>
    </main>
</template>

