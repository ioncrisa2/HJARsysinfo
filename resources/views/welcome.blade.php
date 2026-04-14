<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>Bank Data KJPP HJA'R</title>

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="ui-skip-link">Skip to content</a>

    <div class="relative min-h-dvh overflow-hidden">
        <!-- Visual anchor: contour-style map lines (no gradients, no blur). -->
        <svg
            class="pointer-events-none absolute inset-0 h-full w-full"
            viewBox="0 0 1200 800"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect width="1200" height="800" fill="#f8fafc" />
            <g opacity="0.9">
                <path d="M-40 150 C 120 80, 260 200, 420 140 C 580 80, 760 110, 940 170 C 1100 220, 1220 140, 1260 120" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 220 C 120 150, 260 270, 420 210 C 580 150, 760 180, 940 240 C 1100 290, 1220 210, 1260 190" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 290 C 120 220, 260 340, 420 280 C 580 220, 760 250, 940 310 C 1100 360, 1220 280, 1260 260" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 360 C 120 290, 260 410, 420 350 C 580 290, 760 320, 940 380 C 1100 430, 1220 350, 1260 330" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 430 C 120 360, 260 480, 420 420 C 580 360, 760 390, 940 450 C 1100 500, 1220 420, 1260 400" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 500 C 120 430, 260 550, 420 490 C 580 430, 760 460, 940 520 C 1100 570, 1220 490, 1260 470" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
                <path d="M-40 570 C 120 500, 260 620, 420 560 C 580 500, 760 530, 940 590 C 1100 640, 1220 560, 1260 540" stroke="#0f172a" stroke-opacity="0.07" stroke-width="2" />
            </g>

            <!-- Accent markers -->
            <g opacity="0.95">
                <circle cx="910" cy="250" r="4" fill="#f59e0b" />
                <circle cx="940" cy="420" r="4" fill="#f59e0b" />
                <circle cx="760" cy="520" r="4" fill="#f59e0b" />
            </g>
        </svg>

        <!-- Top bar -->
        <header class="relative z-10">
            <div class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-[var(--radius-sm)] bg-slate-900 text-white shadow-sm">
                            <span class="text-sm font-semibold">H</span>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                Bank Data KJPP HJA'R<span class="text-amber-600">.</span>
                            </p>
                            <p class="truncate text-xs text-slate-500">Data pembanding untuk penilaian properti</p>
                        </div>
                    </div>

                    <a
                        href="/login"
                        class="inline-flex items-center justify-center rounded-[var(--radius-sm)] bg-amber-500 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Masuk
                    </a>
                </div>
            </div>
        </header>

        <!-- Main -->
        <main id="main-content" tabindex="-1" class="relative z-10">
            <section class="mx-auto w-full max-w-7xl px-4 pb-14 pt-10 sm:px-6 sm:pb-20 sm:pt-16">
                <div class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-end">
                    <!-- Copy -->
                    <div class="max-w-xl">
                        <p class="text-xs font-semibold text-amber-700">Portal internal</p>
                        <h1 class="mt-3 text-balance text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl">
                            Cari pembanding. Lihat di peta. Ekspor cepat.
                        </h1>
                        <p class="mt-4 text-pretty text-base leading-relaxed text-slate-600">
                            Satu tempat untuk input dan menelusuri data pembanding properti secara rapi, konsisten,
                            dan mudah diverifikasi.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <a
                                href="/login"
                                class="inline-flex items-center justify-center rounded-[var(--radius-sm)] bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white"
                            >
                                Masuk ke aplikasi
                            </a>
                            <a
                                href="/login"
                                class="inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700"
                            >
                                Lihat dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Proof / features (cardless, list-first) -->
                    <div class="rounded-[var(--radius-lg)] border border-slate-200 bg-white shadow-sm">
                        <div class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-900">Yang bisa Anda lakukan</p>
                            <p class="mt-1 text-xs text-slate-500">Fokus pada input, pencarian, dan pelacakan.</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-800">Pencarian yang langsung terasa</p>
                                <p class="mt-1 text-xs text-slate-600 text-pretty">
                                    Filter lokasi, tanggal, jenis listing, dan jenis objek tanpa bingung.
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-800">Peta untuk cek konteks</p>
                                <p class="mt-1 text-xs text-slate-600 text-pretty">
                                    Lihat koordinat dan sebaran data untuk memastikan relevansi.
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-800">Ekspor saat dibutuhkan</p>
                                <p class="mt-1 text-xs text-slate-600 text-pretty">
                                    Unduh hasil sesuai filter untuk kebutuhan laporan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-lg)] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-sm text-slate-700">
                        Butuh akses? Hubungi admin untuk pembuatan akun.
                    </p>
                    <a
                        href="/login"
                        class="inline-flex items-center justify-center rounded-[var(--radius-sm)] border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700"
                    >
                        Masuk
                    </a>
                </div>
            </section>
        </main>

        <footer class="relative z-10">
            <div class="mx-auto w-full max-w-7xl px-4 pb-10 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-6 text-xs text-slate-500">
                    <span class="ui-tabular">(c) {{ date('Y') }} Bank Data KJPP HJA'R</span>
                    <span class="ui-tabular">v1</span>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>

