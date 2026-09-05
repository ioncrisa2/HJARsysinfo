<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses dokumentasi API | KJPP HJA'R</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100svh; display: grid; place-items: center; padding: 24px; background: #0f172a; color: #f8fafc; font-family: system-ui, sans-serif; line-height: 1.6; }
        main { width: min(100%, 480px); border: 1px solid #475569; border-radius: 16px; padding: clamp(24px, 5vw, 40px); }
        .brand { display: flex; align-items: center; gap: 14px; margin-bottom: 32px; }
        .brand img { width: 44px; height: 44px; }
        .brand p, h1 { margin: 0; }
        .brand small, .hint { color: #cbd5e1; }
        h1 { font-size: clamp(24px, 5vw, 30px); line-height: 1.25; }
        label { display: block; margin-top: 24px; font-weight: 600; }
        input, button { width: 100%; min-height: 48px; border-radius: 8px; font: inherit; }
        input { margin-top: 8px; padding: 10px 14px; background: #1e293b; border: 1px solid #94a3b8; color: #f8fafc; }
        button { margin-top: 20px; padding: 10px 16px; background: #fb923c; border: 0; color: #0f172a; font-weight: 700; cursor: pointer; }
        button:hover { background: #fdba74; }
        :focus-visible { outline: 3px solid #fdba74; outline-offset: 4px; }
        .error { color: #fecaca; }
        .hint { font-size: 14px; }
        a { color: #fdba74; }
    </style>
</head>
<body>
<main>
    <div class="brand">
        <img src="{{ asset('images/h-logo.jpg') }}" alt="">
        <div><p><strong>KJPP HJA'R</strong></p><small>Property Valuation Services</small></div>
    </div>
    <h1>Dokumentasi API</h1>
    <p>Masukkan PIN dari administrator untuk membuka dokumentasi.</p>
    <form method="POST" action="{{ route('docs.unlock') }}">
        @csrf
        <label for="pin">PIN akses</label>
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{8,12}" minlength="8" maxlength="12" autocomplete="current-password" required aria-describedby="pin-hint{{ $error ? ' pin-error' : '' }}" @if($error) aria-invalid="true" @endif>
        <p class="hint" id="pin-hint">8–12 digit. Akses berlaku {{ max(1, (int) config('docs.session_minutes')) }} menit.</p>
        @if($error)
            <p class="error" id="pin-error" role="alert">{{ $error }}</p>
        @endif
        <button type="submit">Buka dokumentasi</button>
    </form>
    @if(session('docs.expires_at'))
        <form method="POST" action="{{ route('docs.logout') }}">
            @csrf
            <button type="submit">Kunci akses dokumentasi</button>
        </form>
    @endif
    <p class="hint"><a href="{{ url('/') }}">Kembali ke beranda</a></p>
</main>
</body>
</html>
