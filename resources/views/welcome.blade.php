<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Bank Data KJPP HJA'R</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
        }

        /* LOGO CSS CONSTRUCTION (Agar mirip gambar referensi) */
        .logo-container {
            width: 48px;
            height: 48px;
            background-color: #003399; /* Biru Tua Frame */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px; /* Ketebalan frame biru */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .logo-inner {
            width: 100%;
            height: 100%;
            background-color: #990000; /* Merah Hati */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-text {
            color: white;
            font-weight: 400; /* H agak tipis/sedang */
            font-size: 24px;
            font-family: sans-serif; /* Font standar bersih */
            line-height: 1;
        }

        /* Kanan: Map Background Pattern */
        .map-container {
            background-image: url('https://cartodb-basemaps-a.global.ssl.fastly.net/dark_all/14/13230/8490.png');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        /* Overlay Gradient */
        .map-overlay {
            background: linear-gradient(90deg, #0f172a 0%, rgba(15, 23, 42, 0.4) 50%, rgba(15, 23, 42, 0.1) 100%);
        }

        /* Style Marker */
        .map-marker {
            position: absolute;
            font-size: 2.5rem;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5));
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-origin: bottom center;
            z-index: 10;
        }

        .map-marker:hover {
            transform: scale(1.2) translateY(-10px);
            z-index: 50;
        }

        /* Warna Marker */
        .marker-primary { color: #d97706; }
        .marker-accent { color: #22c55e; }

        /* Tooltip Marker */
        .marker-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: white;
            color: #333;
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
        .marker-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: white transparent transparent transparent;
        }
        .map-marker:hover .marker-tooltip {
            opacity: 1;
            transform: translateX(-50%) translateY(-12px);
        }
    </style>
</head>
<body class="h-screen w-full flex overflow-hidden text-white">

    <!-- BAGIAN KIRI: Branding & Login Utama -->
    <div class="w-full lg:w-5/12 flex flex-col p-8 md:p-16 relative z-20 bg-slate-900 border-r border-slate-800/50 shadow-2xl justify-center">

        <!-- Logo & Branding Header -->
        <div class="absolute top-12 left-12 md:left-16 flex items-center gap-4">
            <!-- Logo CSS Construction -->
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

        <!-- Main Content Area (Vertically Centered) -->
        <div class="max-w-md">
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">
                Akses Data <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">Pembanding</span>
                Terlengkap.
            </h1>
            <p class="text-slate-400 mb-10 text-base leading-relaxed">
                Selamat datang di Bank Data Properti. Temukan ribuan data pembanding tervalidasi untuk akurasi penilaian properti Anda dengan cepat dan tepat.
            </p>

            <!-- Single Login Button -->
            <button onclick="goToLogin()" class="w-full sm:w-auto px-8 py-4 bg-orange-600 hover:bg-orange-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-orange-600/25 flex items-center justify-center gap-3 group">
                <span>Login Aplikasi</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>

            <!-- Footer Info -->
            <div class="mt-12 pt-8 border-t border-slate-800">
                <p class="text-xs text-slate-500">
                    &copy; <?php echo date('Y') ?> Sistem Informasi Geografis & Database Properti.
                    <br>Versi 1.0.0 (Beta)
                </p>
            </div>
        </div>
    </div>

    <!-- BAGIAN KANAN: Maps dengan Marker Besar -->
    <div class="hidden lg:block lg:w-7/12 map-container overflow-hidden bg-gray-900">
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 map-overlay z-0 pointer-events-none"></div>

        <!-- Area Pins (Marker Lokasi) -->
        <div class="relative w-full h-full" id="mapMarkers">

            <!-- Marker Accent (Lokasi User) -->
            <div class="map-marker marker-accent" style="top: 60%; left: 45%;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Lokasi Penilaian</div>
            </div>

            <!-- Markers Data Pembanding (Random Positions) -->
            <div class="map-marker marker-primary" style="top: 35%; left: 35%; animation-delay: 0.1s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Ruko - Rp 2M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 45%; left: 60%; animation-delay: 0.2s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Tanah - Rp 5M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 25%; left: 55%; animation-delay: 0.3s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Gudang - Rp 15M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 70%; left: 30%; animation-delay: 0.4s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Rumah - Rp 800jt</div>
            </div>
            <div class="map-marker marker-primary" style="top: 50%; left: 75%; animation-delay: 0.5s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Kios - Rp 300jt</div>
            </div>
             <div class="map-marker marker-primary" style="top: 20%; left: 25%; animation-delay: 0.6s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Kavling - Rp 1.2M</div>
            </div>
             <div class="map-marker marker-primary" style="top: 80%; left: 65%; animation-delay: 0.7s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Villa - Rp 4M</div>
            </div>
            <div class="map-marker marker-primary" style="top: 15%; left: 65%; animation-delay: 0.8s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Pabrik - Rp 25M</div>
            </div>
             <div class="map-marker marker-primary" style="top: 40%; left: 20%; animation-delay: 0.9s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Apartemen - Rp 600jt</div>
            </div>
            <div class="map-marker marker-primary" style="top: 65%; left: 80%; animation-delay: 1s;">
                <i class="fa-solid fa-location-dot"></i>
                <div class="marker-tooltip">Rukan - Rp 3.5M</div>
            </div>

            <!-- Cluster Background Pins -->
            <div class="map-marker marker-primary" style="top: 20%; left: 85%; font-size: 1.8rem; opacity: 0.8;">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div class="map-marker marker-primary" style="top: 18%; left: 90%; font-size: 1.8rem; opacity: 0.8;">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>

        <!-- Floating Decoration -->
        <div class="absolute top-8 right-8 z-20 bg-slate-800/90 backdrop-blur-md border border-slate-600 p-3 rounded-xl flex items-center gap-3 shadow-2xl animate-pulse">
            <div class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white text-xs">
                <i class="fa-solid fa-map"></i>
            </div>
            <div class="pr-4">
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Area Terpilih</p>
                <p class="text-xs font-bold text-white">DKI Jakarta & Sekitarnya</p>
            </div>
        </div>
    </div>

    <script>
        function goToLogin() {
            const btn = document.querySelector('button');
            const originalContent = btn.innerHTML;

            // Loading state
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memuat...';
            btn.classList.add('opacity-75', 'cursor-wait');


            setTimeout(() => {

                btn.innerHTML = originalContent;
                btn.classList.remove('opacity-75', 'cursor-wait');
                window.location.href = 'admin/login';
            }, 800);
        }
    </script>
</body>
</html>
