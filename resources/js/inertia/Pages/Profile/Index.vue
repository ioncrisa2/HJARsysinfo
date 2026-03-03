<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";
import TopNavLayout from "../../Layouts/TopNavLayout.vue";

defineOptions({ layout: TopNavLayout });

const page = usePage();
const props = defineProps({
    user: Object,
});

const initials = (name) =>
    (name ?? "U")
        .split(" ")
        .slice(0, 2)
        .map((w) => w[0])
        .join("")
        .toUpperCase();

const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.put("/profile", {
        onSuccess: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Profil" />

    <div class="space-y-5 py-4">

        <!-- Header -->
        <div>
            <p class="text-xs text-slate-400">Akun</p>
            <h1 class="text-xl font-bold text-slate-900">Profil Pengguna</h1>
        </div>

        <Message v-if="page.props.flash?.success" severity="success">
            {{ page.props.flash.success }}
        </Message>

        <div class="grid gap-5 lg:grid-cols-[280px_1fr]">

            <!-- Left: Avatar + info card -->
            <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <!-- Avatar banner -->
                    <div class="h-20 bg-gradient-to-br from-amber-400 to-amber-600" />
                    <div class="px-5 pb-5">
                        <!-- Avatar circle -->
                        <div class="-mt-8 mb-3 flex items-end justify-between">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-4 border-white bg-gradient-to-br from-amber-500 to-amber-700 text-xl font-bold text-white shadow-md">
                                {{ initials(user?.name) }}
                            </div>
                        </div>
                        <p class="text-base font-bold text-slate-900">{{ user?.name ?? "Pengguna" }}</p>
                        <p class="text-sm text-slate-400">{{ user?.email ?? "" }}</p>

                        <div class="mt-4 border-t border-slate-100 pt-4 space-y-2 text-xs text-slate-500">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-shield text-slate-400" style="font-size:11px" />
                                <span>Peran aktif</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="role in user?.roles ?? []"
                                    :key="role"
                                    class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700"
                                >
                                    {{ role }}
                                </span>
                                <span v-if="(user?.roles ?? []).length === 0" class="text-slate-400 italic">Tidak ada peran</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions card -->
                <div v-if="(user?.permissions ?? []).length > 0" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100">
                            <i class="pi pi-key text-amber-600" style="font-size:11px" />
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Hak Akses</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 p-4">
                        <span
                            v-for="perm in user?.permissions ?? []"
                            :key="perm"
                            class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700"
                        >
                            {{ perm }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Edit form -->
            <div class="space-y-4">

                <!-- Info section -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100">
                            <i class="pi pi-user text-amber-600" style="font-size:11px" />
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Informasi Akun</span>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-500">Nama Lengkap</label>
                            <InputText v-model="form.name" class="w-full" placeholder="Nama lengkap" />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-500">Alamat Email</label>
                            <InputText v-model="form.email" type="email" class="w-full" placeholder="email@contoh.com" />
                            <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Password section -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100">
                            <i class="pi pi-lock text-amber-600" style="font-size:11px" />
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Ubah Password</span>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-500">Password Baru</label>
                            <Password
                                v-model="form.password"
                                toggle-mask
                                :feedback="false"
                                class="w-full"
                                input-class="w-full"
                                placeholder="••••••••"
                            />
                            <p class="text-xs text-slate-400">Biarkan kosong jika tidak ingin mengganti.</p>
                            <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-500">Konfirmasi Password</label>
                            <Password
                                v-model="form.password_confirmation"
                                toggle-mask
                                :feedback="false"
                                class="w-full"
                                input-class="w-full"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2">
                    <Button
                        label="Reset"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        @click="form.reset()"
                    />
                    <Button
                        label="Simpan Perubahan"
                        icon="pi pi-save"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>

            </div>
        </div>
    </div>
</template>
