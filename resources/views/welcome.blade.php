<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Bank Data KJPP HJA'R</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: #eef2f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative blobs */
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

        /* Map background tile */
        .map-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://cartodb-basemaps-a.global.ssl.fastly.net/light_all/14/13230/8490.png');
            background-size: cover;
            background-position: center;
            opacity: 0.12;
        }

        /* Grid dot pattern overlay */
        .dot-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, #94a3b8 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.25;
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
            min-height: 520px;
            display: flex;
            overflow: hidden;
            position: relative;
            z-index: 10;
        }

        /* Left panel */
        .card-left {
            flex: 1;
            padding: 48px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid rgba(226, 232, 240, 0.8);
        }

        /* Right panel */
        .card-right {
            width: 340px;
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
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

        /* Stats badge */
        .stat-badge {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Button */
        .btn-login {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.35);
            width: 100%;
            justify-content: center;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #fb923c, #f97316);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(234, 88, 12, 0.45);
        }
        .btn-login:active { transform: translateY(0px); }

        /* Feature pill */
        .feature-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 6px 12px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Marker dot on right panel */
        .panel-marker {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.25);
            display: inline-block;
            flex-shrink: 0;
        }

        /* Animate card in */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card { animation: fadeUp 0.6s ease forwards; }
    </style>
</head>
<body>

    <!-- Background layers -->
    <div class="map-bg"></div>
    <div class="dot-pattern"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Centered Card -->
    <div class="card mx-4">

        <!-- LEFT: Branding & Login -->
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

            <!-- Main Content -->
            <div class="flex-1 flex flex-col justify-center py-6">
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="feature-pill"><i class="fa-solid fa-circle-check text-green-500"></i> Data Tervalidasi</span>
                    <span class="feature-pill"><i class="fa-solid fa-map-location-dot text-orange-500"></i> Berbasis GIS</span>
                    <span class="feature-pill"><i class="fa-solid fa-bolt text-blue-500"></i> Real-time</span>
                </div>

                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 leading-tight mb-4">
                    Akses Data <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">Pembanding</span>
                    Terlengkap.
                </h1>

                <p class="text-slate-500 text-sm leading-relaxed mb-8 max-w-sm">
                    Temukan ribuan data pembanding properti tervalidasi untuk akurasi penilaian Anda — cepat, tepat, dan terpercaya.
                </p>

                <button onclick="goToLogin()" class="btn-login" id="loginBtn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Login Aplikasi</span>
                    <i class="fa-solid fa-arrow-right ml-auto"></i>
                </button>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-400">
                    &copy; <?php echo date('Y') ?> Bank Data Properti KJPP HJA'R &nbsp;·&nbsp; v1.0.0 Beta
                </p>
            </div>
        </div>

        <!-- RIGHT: Stats & Info Panel -->
        <div class="card-right">
            <div class="relative z-10">
                <p class="text-xs text-slate-400 uppercase tracking-widest font-semibold mb-1">Cakupan Area</p>
                <h3 class="text-white font-bold text-lg leading-tight mb-1">DKI Jakarta</h3>
                <p class="text-slate-400 text-xs">& Kawasan Sekitarnya</p>
            </div>

            <!-- Stats -->
            <div class="relative z-10 flex flex-col gap-3 my-6">
                <div class="stat-badge">
                    <div class="w-9 h-9 rounded-xl bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-database text-orange-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-base leading-tight">12.400+</p>
                        <p class="text-slate-400 text-xs">Data Properti Aktif</p>
                    </div>
                </div>

                <div class="stat-badge">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-map-location-dot text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-base leading-tight">34 Wilayah</p>
                        <p class="text-slate-400 text-xs">Kecamatan Tercakup</p>
                    </div>
                </div>

                <div class="stat-badge">
                    <div class="w-9 h-9 rounded-xl bg-green-500/20 flex items-center justify-center flex-shrink-0">
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
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> Rumah
                    </span>
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> Ruko
                    </span>
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> Tanah
                    </span>
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> Gudang
                    </span>
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> Apartemen
                    </span>
                    <span class="text-xs text-slate-300 bg-white/10 border border-white/10 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="panel-marker"></span> & Lainnya
                    </span>
                </div>
            </div>
        </div>

    </div>

    <script>
        function goToLogin() {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>Memuat...</span>';
            btn.style.opacity = '0.75';
            btn.style.cursor = 'wait';
            btn.disabled = true;
            setTimeout(() => { window.location.href = '/login'; }, 800);
        }
    </script>
</body>
</html>
