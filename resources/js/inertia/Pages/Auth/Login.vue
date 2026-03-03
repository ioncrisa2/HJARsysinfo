<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Password from "primevue/password";
import ScrollTop from "primevue/scrolltop";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});
const page = usePage();

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Login — Bank Data KJPP HJA'R" />

    <main class="login-root h-screen w-full flex items-center justify-center overflow-hidden relative">

        <!-- Background layers -->
        <div class="map-bg"></div>
        <div class="dot-pattern"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <!-- Centered Card -->
        <div class="card mx-4 relative z-10">

            <!-- LEFT: Login Form -->
            <div class="card-left">

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-2">
                    <div class="logo-container">
                        <div class="logo-inner">
                            <span class="logo-text">H</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-base leading-tight">KJPP HJA'R</p>
                        <p class="text-xs text-slate-400 font-medium">Property Valuation Services</p>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="flex-1 flex flex-col justify-center py-6">
                    <div class="mb-7">
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 leading-tight mb-2">
                            Selamat
                            <span class="text-transparent bg-clip-text bg-linier-to-r from-orange-500 to-orange-700">Datang.</span>
                        </h1>
                        <p class="text-slate-500 text-sm">
                            Masuk untuk mengakses data pembanding properti tervalidasi.
                        </p>
                    </div>

                    <!-- Form -->
                    <form class="space-y-4" @submit.prevent="submit">

                        <Message v-if="page.props.flash?.error" severity="warn" class="rounded-xl">
                            {{ page.props.flash.error }}
                        </Message>

                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label for="email" class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Email</label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                class="w-full"
                                input-class="form-input"
                                type="email"
                                placeholder="nama@perusahaan.com"
                                autocomplete="email"
                                autofocus
                            />
                            <small v-if="form.errors.email" class="text-red-500 text-xs">{{ form.errors.email }}</small>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <label for="password" class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Password</label>
                            <Password
                                id="password"
                                v-model="form.password"
                                class="w-full"
                                input-class="form-input w-full"
                                :feedback="false"
                                toggle-mask
                                placeholder="••••••••"
                                autocomplete="current-password"
                            />
                            <small v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</small>
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center gap-2.5">
                            <Checkbox id="remember" v-model="form.remember" binary />
                            <label for="remember" class="text-sm text-slate-500 cursor-pointer select-none">Ingat saya</label>
                        </div>

                        <Message v-if="form.hasErrors && !form.errors.email && !form.errors.password" severity="error" class="rounded-xl">
                            Login gagal, silakan coba kembali.
                        </Message>

                        <!-- Submit -->
                        <button
                            type="submit"
                            class="btn-login w-full flex items-center justify-center gap-3 group"
                            :disabled="form.processing"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner"></i>
                            <i v-else class="pi pi-sign-in"></i>
                            <span>{{ form.processing ? 'Memuat...' : 'Masuk ke Aplikasi' }}</span>
                            <i v-if="!form.processing" class="pi pi-arrow-right ml-auto group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>

                    <!-- Links -->
                    <div class="mt-5 pt-5 border-t border-slate-100 flex items-center justify-between text-sm">
                        <Link href="/" class="text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1.5">
                            <i class="pi pi-arrow-left text-xs"></i>
                            Kembali
                        </Link>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        &copy; {{ new Date().getFullYear() }} Bank Data Properti KJPP HJA'R &nbsp;·&nbsp; v1.0.0 Beta
                    </p>
                </div>
            </div>

            <!-- RIGHT: Info Panel -->
            <div class="card-right">
                <div class="relative z-10">
                    <p class="text-xs text-slate-400 uppercase tracking-widest font-semibold mb-1">Cakupan Area</p>
                    <h3 class="text-white font-bold text-lg leading-tight mb-1">DKI Jakarta</h3>
                    <p class="text-slate-400 text-xs">& Kawasan Sekitarnya</p>
                </div>

                <!-- Stats -->
                <div class="relative z-10 flex flex-col gap-3 my-6">
                    <div class="stat-badge">
                        <div class="stat-icon bg-orange-500/20">
                            <i class="fa-solid fa-database text-orange-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold text-base leading-tight">12.400+</p>
                            <p class="text-slate-400 text-xs">Data Properti Aktif</p>
                        </div>
                    </div>

                    <div class="stat-badge">
                        <div class="stat-icon bg-blue-500/20">
                            <i class="fa-solid fa-map-location-dot text-blue-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold text-base leading-tight">34 Wilayah</p>
                            <p class="text-slate-400 text-xs">Kecamatan Tercakup</p>
                        </div>
                    </div>

                    <div class="stat-badge">
                        <div class="stat-icon bg-green-500/20">
                            <i class="fa-solid fa-shield-halved text-green-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold text-base leading-tight">Terverifikasi</p>
                            <p class="text-slate-400 text-xs">Data Selalu Diperbarui</p>
                        </div>
                    </div>
                </div>

                <!-- Property type tags -->
                <div class="relative z-10">
                    <p class="text-xs text-slate-500 mb-3 font-medium">Tipe Properti Tersedia</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="type in ['Rumah','Ruko','Tanah','Gudang','Apartemen','& Lainnya']" :key="type"
                            class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                            <span class="panel-marker"></span> {{ type }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <ScrollTop :threshold="260" />
</template>

<style scoped>
.login-root {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background-color: #eef2f7;
}

/* Background layers */
.map-bg {
    position: absolute;
    inset: 0;
    background-image: url('https://cartodb-basemaps-a.global.ssl.fastly.net/light_all/14/13230/8490.png');
    background-size: cover;
    background-position: center;
    opacity: 0.12;
}
.dot-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, #94a3b8 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: 0.25;
}
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.45;
    pointer-events: none;
}
.blob-1 {
    width: 600px; height: 600px;
    background: radial-gradient(circle, #fdba74, #f97316);
    top: -180px; right: -150px;
}
.blob-2 {
    width: 500px; height: 500px;
    background: radial-gradient(circle, #bfdbfe, #3b82f6);
    bottom: -200px; left: -150px;
}
.blob-3 {
    width: 300px; height: 300px;
    background: radial-gradient(circle, #bbf7d0, #22c55e);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
}

/* Logo */
.logo-container {
    width: 44px; height: 44px;
    background-color: #003399;
    display: flex; align-items: center; justify-content: center;
    padding: 5px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 51, 153, 0.25);
    flex-shrink: 0;
}
.logo-inner {
    width: 100%; height: 100%;
    background-color: #990000;
    border-radius: 3px;
    display: flex; align-items: center; justify-content: center;
}
.logo-text {
    color: white; font-weight: 400; font-size: 22px;
    font-family: sans-serif; line-height: 1;
}

/* Card */
.card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    box-shadow:
        0 32px 64px rgba(0, 0, 0, 0.10),
        0 8px 24px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255,255,255,1);
    border-radius: 24px;
    width: 100%;
    max-width: 920px;
    display: flex;
    overflow: hidden;
    animation: fadeUp 0.6s ease forwards;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Left panel */
.card-left {
    flex: 1;
    padding: 40px 44px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid rgba(226, 232, 240, 0.8);
}

/* Right panel */
.card-right {
    width: 320px;
    background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
    padding: 40px 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.card-right::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(249,115,22,0.3), transparent 70%);
    border-radius: 50%;
}
.card-right::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(59,130,246,0.2), transparent 70%);
    border-radius: 50%;
}

/* Stat badges */
.stat-badge {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.stat-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* Panel marker dot */
.panel-marker {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #f97316;
    box-shadow: 0 0 0 3px rgba(249,115,22,0.25);
    display: inline-block;
    flex-shrink: 0;
}

/* Login Button */
.btn-login {
    padding: 14px 28px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 8px 20px rgba(234, 88, 12, 0.35);
}
.btn-login:hover:not(:disabled) {
    background: linear-gradient(135deg, #fb923c, #f97316);
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(234, 88, 12, 0.45);
}
.btn-login:disabled {
    opacity: 0.7;
    cursor: wait;
}

/* Form inputs — light theme */
:deep(.p-inputtext) {
    background-color: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #1e293b !important;
    border-radius: 10px !important;
    padding: 0.7rem 1rem !important;
    width: 100%;
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s;
}
:deep(.p-inputtext:focus) {
    border-color: #f97316 !important;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15) !important;
    outline: none !important;
}
:deep(.p-inputtext::placeholder) {
    color: #cbd5e1 !important;
}

/* PrimeVue Password wrapper */
:deep(.p-password) {
    width: 100%;
}
:deep(.p-password input) {
    background-color: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #1e293b !important;
    border-radius: 10px !important;
    padding: 0.7rem 1rem !important;
    width: 100%;
}
:deep(.p-password input:focus) {
    border-color: #f97316 !important;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15) !important;
}
:deep(.p-password-toggle-icon) {
    color: #94a3b8 !important;
}

/* Checkbox */
:deep(.p-checkbox .p-checkbox-box) {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    border-radius: 5px !important;
}
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background-color: #ea580c !important;
    border-color: #ea580c !important;
}
</style>
