@push('styles')
    <!-- Tailwind CDN to ensure custom utility classes are available on the login page -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'jakarta': ['\"Plus Jakarta Sans\"', 'sans-serif'],
                    },
                },
            },
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-login {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
        }

        .custom-login .logo-container {
            width: 48px;
            height: 48px;
            background-color: #003399;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .custom-login .logo-inner {
            width: 100%;
            height: 100%;
            background-color: #990000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-login .logo-text {
            color: white;
            font-weight: 400;
            font-size: 24px;
            line-height: 1;
        }

        .custom-login .glass-panel {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }

        .custom-login .map-side {
            background-image: url('https://cartodb-basemaps-a.global.ssl.fastly.net/dark_all/14/13230/8490.png');
            background-size: cover;
            background-position: center;
        }

        .custom-login .map-overlay {
            background: linear-gradient(90deg, #0f172a 0%, rgba(15, 23, 42, 0.4) 50%, rgba(15, 23, 42, 0.05) 100%);
        }

        .custom-login .map-marker {
            position: absolute;
            font-size: 2.25rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.45));
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-origin: bottom center;
            cursor: pointer;
            z-index: 10;
        }

        .custom-login .map-marker:hover {
            transform: scale(1.12) translateY(-10px);
        }

        .custom-login .marker-primary {
            color: #d97706;
        }

        .custom-login .marker-accent {
            color: #22c55e;
        }

        .custom-login .marker-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: white;
            color: #1f2937;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .custom-login .marker-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: white transparent transparent transparent;
        }

        .custom-login .map-marker:hover .marker-tooltip {
            opacity: 1;
            transform: translateX(-50%) translateY(-12px);
        }

        .custom-login .fi-form-actions .fi-btn {
            background: linear-gradient(90deg, #ea580c, #f97316);
            color: #fff;
            border: none;
            box-shadow: 0 10px 30px -10px rgba(234, 88, 12, 0.7);
        }

        .custom-login .fi-form-actions .fi-btn:hover {
            background: linear-gradient(90deg, #f97316, #fb923c);
        }
    </style>
@endpush

<div class="custom-login dark min-h-screen lg:min-h-screen lg:h-screen w-full text-white flex flex-col lg:flex-row overflow-hidden">
    <!-- Left side: brand + form -->
    <div class="relative w-full lg:w-5/12 flex flex-col flex-1 min-h-screen p-8 md:p-16 z-10 bg-slate-900 border-r border-slate-800/50 justify-center">
        <!-- Logo -->
        <div class="absolute top-10 left-8 md:left-16 flex items-center gap-4">
            <div class="logo-container">
                <div class="logo-inner">
                    <span class="logo-text">H</span>
                </div>
            </div>

            <div class="flex flex-col justify-center h-12">
                <h3 class="font-bold text-xl tracking-tight text-white leading-none mb-1">KJPP HJA'R</h3>
                <p class="text-xs text-slate-400 font-medium tracking-wide">Property Valuation Services</p>
            </div>
        </div>

        <div class="max-w-md w-full space-y-8 mx-auto">
            <div>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    Selamat Datang<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">Masuk ke Bank Data</span>
                </h1>
                <p class="text-slate-400 text-base leading-relaxed">
                    Gunakan akun Filament Anda untuk mengakses data pembanding yang sudah tervalidasi.
                </p>
            </div>

            <div class="glass-panel rounded-2xl p-6 md:p-8 backdrop-blur-xl border border-slate-800/60">
                <x-filament-panels::form id="form" wire:submit="authenticate" class="space-y-6">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                        class="pt-2"
                    />
                </x-filament-panels::form>
            </div>

            <div class="pt-4 border-t border-slate-800/60 text-xs text-slate-500">
                &copy; {{ date('Y') }} Sistem Informasi Geografis &amp; Database Properti — Versi 1.0.0 (Beta)
            </div>
        </div>
    </div>

    <!-- Right side: map visual -->
    <div class="relative hidden lg:block lg:w-7/12 lg:min-h-screen map-side overflow-hidden">
        <div class="absolute inset-0 map-overlay"></div>

        <div class="relative w-full h-full" id="mapMarkers">
            <div class="map-marker marker-accent" style="top: 60%; left: 45%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Lokasi Penilaian</div>
            </div>

            <div class="map-marker marker-primary" style="top: 35%; left: 35%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Ruko - Rp 2M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 45%; left: 60%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Tanah - Rp 5M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 25%; left: 55%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Gudang - Rp 15M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 70%; left: 30%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Rumah - Rp 800jt</div>
            </div>
            <div class="map-marker marker-primary" style="top: 50%; left: 75%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Kios - Rp 300jt</div>
            </div>
            <div class="map-marker marker-primary" style="top: 20%; left: 25%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Kavling - Rp 1.2M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 80%; left: 65%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Villa - Rp 4M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 15%; left: 65%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Pabrik - Rp 25M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 40%; left: 20%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Apartemen - Rp 600jt</div>
            </div>
            <div class="map-marker marker-primary" style="top: 65%; left: 80%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Rukan - Rp 3.5M</div>
            </div>

            <div class="map-marker marker-primary" style="top: 20%; left: 85%; font-size: 1.8rem; opacity: 0.85;">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div class="map-marker marker-primary" style="top: 18%; left: 90%; font-size: 1.8rem; opacity: 0.85;">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>

        <div class="absolute top-8 right-8 z-20 bg-slate-800/90 backdrop-blur-md border border-slate-600 p-3 rounded-xl flex items-center gap-3 shadow-2xl">
            <div class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white text-xs">
                <i class="fa-solid fa-map"></i>
            </div>
            <div class="pr-4">
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Area Terpilih</p>
                <p class="text-xs font-bold text-white">DKI Jakarta &amp; Sekitarnya</p>
            </div>
        </div>
    </div>
</div>
