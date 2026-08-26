<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        }

        if ($user->deactivated_at !== null) {
            if ($request->hasSession()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'status' => 'error',
                'code' => 'USER_DEACTIVATED',
                'message' => 'Akun Anda sedang dinonaktifkan.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
