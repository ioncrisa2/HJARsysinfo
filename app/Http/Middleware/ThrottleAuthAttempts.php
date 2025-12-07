<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAuthAttempts
{
    protected const MAX_ATTEMPTS = 5;
    protected const DECAY_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, self::DECAY_MINUTES * 60);

        $response = $next($request);

        // Clear rate limit on successful login
        if ($response->status() === 200) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    protected function throttleKey(Request $request): string
    {
        return 'login_' . strtolower($request->input('email')) . '_' . $request->ip();
    }
}
