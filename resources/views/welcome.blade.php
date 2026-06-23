<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>Bank Data KJPP HJA'R</title>

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-slate-900 text-slate-900 antialiased">
    <a href="#main-content" class="ui-skip-link">Skip to content</a>

    <div class="relative flex min-h-dvh flex-col overflow-hidden">
        <img
            src="{{ asset('images/landing-property-background.png') }}"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-20 size-full object-cover"
        >
        <div class="pointer-events-none absolute inset-0 -z-10 bg-white/65 backdrop-blur-[3px]" aria-hidden="true"></div>

        <header class="border-b border-white/40 bg-white/65 backdrop-blur-md">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-[var(--radius-sm)] bg-white ring-1 ring-slate-200">
                        <img src="{{ asset('images/h-logo.jpg') }}" alt="" class="size-full object-cover" aria-hidden="true">
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-950">Bank Data KJPP HJA'R</p>
                        <p class="truncate text-xs text-slate-500">Data pembanding properti</p>
                    </div>
                </div>

                <a
                    href="{{ route('login') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-sm)] border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                >
                    Masuk
                </a>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="flex flex-1 items-center">
            <section class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                <div class="max-w-3xl">
                    <h1 class="text-balance text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl">
                        Bank Data KJPP HJA'R
                    </h1>
                    <p class="mt-4 max-w-2xl text-pretty text-base leading-7 text-slate-600 sm:text-lg">
                        Portal kerja internal untuk menyimpan, mencari, memetakan, dan mengekspor data pembanding properti.
                    </p>

                    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-sm)] bg-amber-500 px-5 text-sm font-semibold text-white hover:bg-amber-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 sm:w-auto"
                        >
                            Masuk ke aplikasi
                        </a>
                        <p class="text-sm text-slate-500">
                            Butuh akses? Hubungi admin internal.
                        </p>
                    </div>
                </div>

                <div class="mt-12 border-t border-slate-200 pt-6">
                    <ul class="flex flex-col gap-3 text-sm font-medium text-slate-700 sm:flex-row sm:flex-wrap sm:gap-x-8">
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            Cari data pembanding
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                            Filter lokasi dan jenis objek
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                            Lihat sebaran peta
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                            Ekspor hasil kerja
                        </li>
                    </ul>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/40 bg-white/65 backdrop-blur-md">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <span class="ui-tabular">(c) {{ date('Y') }} Bank Data KJPP HJA'R</span>
                <span class="ui-tabular">Internal system v1</span>
            </div>
        </footer>
    </div>
</body>
</html>
