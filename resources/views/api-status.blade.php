<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $name }} · Status</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --background: #08111f;
            --line: #26364d;
            --text: #f4f7fb;
            --muted: #9eacc0;
            --success: #4ade80;
            --success-soft: #163c2b;
            --warning: #fbbf24;
            --warning-soft: #493614;
            --focus: #fb923c;
        }
        * { box-sizing: border-box; }
        html { font-family: "Plus Jakarta Sans", sans-serif; }
        body { margin: 0; min-height: 100vh; color: var(--text); background: var(--background); }
        body::before { content: ""; position: fixed; inset: 0; pointer-events: none; background: linear-gradient(130deg, rgba(234, 88, 12, .10), transparent 34%, rgba(37, 99, 235, .09)); }
        ::selection { color: #08111f; background: #fdba74; }
        .shell { position: relative; width: min(100% - 40px, 920px); margin: 0 auto; padding: clamp(44px, 9vh, 92px) 0 40px; }
        .masthead { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-bottom: 22px; border-bottom: 1px solid var(--line); }
        .brand { display: flex; align-items: center; gap: 13px; }
        .logo { width: 42px; height: 42px; object-fit: cover; background: white; }
        .brand strong { display: block; font-size: 15px; }
        .brand span { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; }
        .version { color: var(--muted); font-size: 12px; font-variant-numeric: tabular-nums; }
        .summary { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(260px, .75fr); gap: clamp(32px, 7vw, 76px); align-items: end; padding: clamp(52px, 8vw, 82px) 0 54px; }
        h1 { max-width: 13ch; margin: 0; font-size: clamp(40px, 7vw, 72px); line-height: 1.02; letter-spacing: -.04em; }
        .intro { max-width: 62ch; margin: 22px 0 0; color: #c4cedc; font-size: 16px; line-height: 1.75; }
        .state { padding: 22px 0 4px; border-top: 1px solid var(--line); }
        .state-line { display: flex; align-items: center; gap: 11px; font-weight: 700; }
        .pulse { width: 11px; height: 11px; flex: 0 0 auto; border-radius: 50%; background: {{ $status === 'operational' ? 'var(--success)' : 'var(--warning)' }}; box-shadow: 0 0 0 7px {{ $status === 'operational' ? 'var(--success-soft)' : 'var(--warning-soft)' }}; animation: breathe 2.6s ease-in-out infinite; }
        @keyframes breathe { 50% { box-shadow: 0 0 0 11px transparent; } }
        .state p { margin: 13px 0 0; color: var(--muted); font-size: 13px; line-height: 1.65; }
        .checks { border-top: 1px solid var(--line); }
        .check { display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center; min-height: 70px; border-bottom: 1px solid var(--line); }
        .check-name { font-size: 14px; font-weight: 600; }
        .badge { padding: 7px 11px; border-radius: 999px; color: {{ $status === 'operational' ? '#86efac' : '#fde68a' }}; background: {{ $status === 'operational' ? 'var(--success-soft)' : 'var(--warning-soft)' }}; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .check:first-child .badge { color: #86efac; background: var(--success-soft); }
        .footer { display: flex; justify-content: space-between; gap: 24px; align-items: center; padding-top: 26px; color: var(--muted); font-size: 12px; }
        .links { display: flex; gap: 20px; }
        a { color: #d7dfeb; text-underline-offset: 4px; }
        a:hover { color: white; }
        a:focus-visible { outline: 2px solid var(--focus); outline-offset: 5px; border-radius: 2px; }
        @media (prefers-reduced-motion: reduce) { .pulse { animation: none; } }
        @media (max-width: 680px) {
            .shell { width: min(100% - 32px, 920px); padding-top: 28px; }
            .masthead { align-items: flex-start; }
            .summary { grid-template-columns: 1fr; padding: 50px 0 42px; }
            .state { margin-top: 6px; }
            .footer { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <header class="masthead">
            <div class="brand">
                <img class="logo" src="{{ asset('images/h-logo.jpg') }}" alt="Logo KJPP HJA'R">
                <div><strong>{{ $name }}</strong><span>Application Programming Interface</span></div>
            </div>
            <span class="version">API v{{ $version }}</span>
        </header>
        <section class="summary" aria-labelledby="status-heading">
            <div>
                <h1 id="status-heading">{{ $status === 'operational' ? 'API siap menerima request.' : 'API online, layanan terganggu.' }}</h1>
                <p class="intro">
                    @if ($status === 'operational')
                        Layanan aplikasi dan koneksi database merespons dengan normal. Gunakan endpoint API sesuai dokumentasi dan autentikasi yang berlaku.
                    @else
                        Aplikasi dapat dijangkau, tetapi koneksi database belum tersedia. Periksa konfigurasi database dan jalankan migration sebelum mengirim request API.
                    @endif
                </p>
            </div>
            <div class="state" role="status">
                <div class="state-line"><span class="pulse" aria-hidden="true"></span><span>{{ $status === 'operational' ? 'Semua sistem operasional' : 'Layanan terdegradasi' }}</span></div>
                <p>Diperiksa {{ $timestamp }}. Halaman ini tidak menampilkan kredensial atau detail internal server.</p>
            </div>
        </section>
        <section class="checks" aria-label="Pemeriksaan layanan">
            <div class="check"><span class="check-name">Aplikasi Laravel</span><span class="badge">Online</span></div>
            <div class="check"><span class="check-name">Koneksi database</span><span class="badge">{{ $checks['database'] === 'online' ? 'Online' : 'Tidak tersedia' }}</span></div>
        </section>
        <footer class="footer">
            <span>Base path API: <strong>/api/v1</strong></span>
            <nav class="links" aria-label="Tautan layanan"><a href="{{ $documentation }}">Dokumentasi API</a><a href="{{ $health }}">Liveness check</a></nav>
        </footer>
    </main>
</body>
</html>
