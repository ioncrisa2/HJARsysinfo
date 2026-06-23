<script setup>
import { computed } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    submitUrl: { type: String, required: true },
    expiresAt: { type: String, default: null },
});

const page = usePage();
const form = useForm({
    display_name: "",
    phone: "",
    password: "",
    password_confirmation: "",
});

const expiresLabel = computed(() => {
    if (!props.expiresAt) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "full",
        timeStyle: "short",
    }).format(new Date(props.expiresAt));
});

const submit = () => {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Register Data Contributor" />

    <main class="min-h-dvh bg-slate-100 px-4 py-8 text-slate-900">
        <div class="mx-auto flex min-h-[calc(100dvh-4rem)] w-full max-w-md items-center">
            <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center gap-3">
                    <img :src="'/images/h-logo.jpg'" alt="KJPP HJAR" class="h-11 w-11 rounded-lg object-cover" />
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-600">Bank Data KJPP HJAR</p>
                        <h1 class="text-lg font-bold text-slate-950">Registrasi Data Contributor</h1>
                    </div>
                </div>

                <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    Link ini berlaku sampai <span class="font-semibold text-slate-800">{{ expiresLabel }}</span>.
                </div>

                <div
                    v-if="page.props.flash?.error"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"
                    role="alert"
                >
                    {{ page.props.flash.error }}
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="display_name" class="text-sm font-semibold text-slate-700">
                            Nama singkat / nama panggilan
                        </label>
                        <input
                            id="display_name"
                            v-model="form.display_name"
                            type="text"
                            autocomplete="name"
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            :aria-invalid="Boolean(form.errors.display_name)"
                            :aria-describedby="form.errors.display_name ? 'display-name-error' : undefined"
                        />
                        <p
                            v-if="form.errors.display_name"
                            id="display-name-error"
                            class="mt-1 text-xs font-medium text-red-600"
                            role="alert"
                        >
                            {{ form.errors.display_name }}
                        </p>
                    </div>

                    <div>
                        <label for="phone" class="text-sm font-semibold text-slate-700">Nomor telepon</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            :aria-invalid="Boolean(form.errors.phone)"
                            :aria-describedby="form.errors.phone ? 'phone-error' : undefined"
                        />
                        <p
                            v-if="form.errors.phone"
                            id="phone-error"
                            class="mt-1 text-xs font-medium text-red-600"
                            role="alert"
                        >
                            {{ form.errors.phone }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            :aria-invalid="Boolean(form.errors.password)"
                            :aria-describedby="form.errors.password ? 'password-error' : undefined"
                        />
                        <p
                            v-if="form.errors.password"
                            id="password-error"
                            class="mt-1 text-xs font-medium text-red-600"
                            role="alert"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-semibold text-slate-700">
                            Konfirmasi password
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <i class="pi pi-send text-xs" aria-hidden="true" />
                        Submit request
                    </button>
                </form>
            </section>
        </div>
    </main>
</template>
