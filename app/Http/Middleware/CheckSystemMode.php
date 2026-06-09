<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

class CheckSystemMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mode = SystemSetting::get('system_mode', 'live');

        if ($mode !== 'live') {
            // Check if user is logged in and is a super admin
            // We assume 'super_admin' role exists based on previous plans.
            // Spatie permission check
            if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                return $next($request);
            }

            // Exclude the login route so admins can still login!
            if ($request->is('login') || $request->is('logout') || $request->is('api/auth/*')) {
                return $next($request);
            }

            // Return 503 maintenance mode response
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Sistem sedang dalam pemeliharaan.',
                    'status' => 'maintenance'
                ], 503);
            }

            // Return a view or abort
            abort(503, 'Sistem sedang dalam pemeliharaan.');
        }

        return $next($request);
    }
}
