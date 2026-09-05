<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocsPinAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class DocsPinController extends Controller
{
    public function show(): Response
    {
        abort_unless((string) config('docs.pin') !== '', 403);

        return $this->form();
    }

    public function store(Request $request): Response
    {
        $configured = (string) config('docs.pin');
        abort_unless(preg_match('/\A[0-9]{8,12}\z/', $configured), 403);

        // A global limit also bounds attempts when clients rotate IPs.
        $limits = ['docs-pin:ip:'.hash('sha256', (string) $request->ip()) => 5, 'docs-pin:global' => 100];
        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                return $this->form("Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.", 429)
                    ->header('Retry-After', (string) $seconds);
            }
        }
        foreach ($limits as $key => $max) {
            RateLimiter::hit($key, 900);
        }

        $pin = $request->input('pin');
        if (! is_string($pin) || ! preg_match('/\A[0-9]{8,12}\z/', $pin)
            || ! hash_equals(hash('sha256', $configured), hash('sha256', $pin))) {
            // Render directly: never flash the submitted PIN to session/old input.
            return $this->form('PIN tidak sesuai. Periksa kembali PIN Anda.', 422);
        }

        $request->session()->regenerate();
        $request->session()->put('docs', [
            'fingerprint' => DocsPinAccess::fingerprint(),
            'expires_at' => now()->addMinutes(max(1, (int) config('docs.session_minutes')))->timestamp,
        ]);

        return redirect('/docs/api')->header('Cache-Control', 'no-store, private');
    }

    public function destroy(Request $request): Response
    {
        $request->session()->forget('docs');
        $request->session()->regenerate();

        return redirect()->route('docs.login')->header('Cache-Control', 'no-store, private');
    }

    private function form(?string $error = null, int $status = 200): Response
    {
        return response()->view('docs.login', compact('error'), $status)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
