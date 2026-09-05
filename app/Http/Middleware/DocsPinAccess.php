<?php

namespace App\Http\Middleware;

use Closure;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocsPinAccess
{
    public static function fingerprint(): string
    {
        return hash_hmac('sha256', (string) config('docs.pin'), (string) config('app.key'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ((string) config('docs.pin') === '') {
            $response = app(RestrictedDocsAccess::class)->handle($request, $next);
        } else {
            $authorized = preg_match('/\A[0-9]{8,12}\z/', (string) config('docs.pin'))
                && (int) $request->session()->get('docs.expires_at', 0) > now()->timestamp
                && hash_equals(self::fingerprint(), (string) $request->session()->get('docs.fingerprint', ''));

            if (! $authorized) {
                $request->session()->forget('docs');
                $response = $request->expectsJson() || $request->is('docs/api.json')
                    ? response()->json(['message' => 'Masukkan PIN melalui halaman dokumentasi.'], 401)
                    : redirect()->route('docs.login');
            } else {
                $response = $next($request);
            }
        }

        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
