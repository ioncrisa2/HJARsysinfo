<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | HTTP Version Not Supported</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0f172a;
            --card: rgba(15, 23, 42, 0.82);
            --border: rgba(148, 163, 184, 0.28);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #ea580c;
            --accent-light: #fb923c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text);
            font-family: "Plus Jakarta Sans", sans-serif;
            background:
                radial-gradient(1100px 500px at 15% 10%, rgba(234, 88, 12, 0.16), transparent 65%),
                radial-gradient(1000px 500px at 85% 95%, rgba(59, 130, 246, 0.14), transparent 65%),
                var(--bg);
        }

        .card {
            width: min(100%, 760px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            background: var(--card);
            backdrop-filter: blur(8px);
            box-shadow: 0 25px 50px -14px rgba(0, 0, 0, 0.5);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .logo-container {
            width: 44px;
            height: 44px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #003399;
        }

        .logo-inner {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #990000;
        }

        .logo-text {
            color: #fff;
            font-size: 22px;
            font-weight: 500;
            line-height: 1;
        }

        .brand-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.2;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.04em;
        }

        h1 {
            margin: 0;
            font-size: clamp(28px, 3.8vw, 42px);
            line-height: 1.15;
        }

        .gradient {
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .lead {
            margin: 12px 0 0;
            font-size: 16px;
            color: #cbd5e1;
            max-width: 60ch;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: filter 160ms ease, transform 160ms ease;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
        }

        .btn-secondary {
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.55);
        }

        .btn:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
        }

        .footnote {
            margin-top: 24px;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 640px) {
            .card {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <div class="logo-container">
                <div class="logo-inner">
                    <span class="logo-text">H</span>
                </div>
            </div>
            <div>
                <p class="brand-title">KJPP HJA'R</p>
                <p class="brand-subtitle">Property Valuation Services</p>
            </div>
        </div>

        <h1>
            Versi <span class="gradient">HTTP tidak didukung</span>
        </h1>
        <p class="lead">
            Server tidak dapat memproses permintaan karena versi HTTP pada request tidak didukung.
            Silakan coba ulang dari browser/perangkat lain.
        </p>

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Kembali ke beranda</a>
            <a class="btn btn-secondary" href="{{ url()->current() }}">Muat ulang halaman</a>
        </div>

        <p class="footnote">HTTP 505 - HTTP Version Not Supported</p>
    </main>
</body>
</html>
