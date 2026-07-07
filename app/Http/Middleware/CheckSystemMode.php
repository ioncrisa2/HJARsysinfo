<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mode = SystemSetting::getFresh('system_mode', 'live');

        if ($mode !== 'live') {
            if ($this->isAllowedDuringRestrictedMode($request)) {
                return $next($request);
            }

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Sistem sedang dalam pemeliharaan.',
                    'status' => 'maintenance',
                ], 503);
            }

            abort(503, 'Sistem sedang dalam pemeliharaan.');
        }

        return $next($request);
    }

    private function isAllowedDuringRestrictedMode(Request $request): bool
    {
        if ($request->is('login') || $request->is('logout') || $request->is('api/auth/*')) {
            return true;
        }

        // Let unauthenticated application requests reach auth so super admins
        // can still be redirected to login instead of being trapped behind 503.
        if ($request->is('app') || $request->is('app/*')) {
            return ! $request->user() || $request->user()->hasRole('super_admin');
        }

        return $request->user()?->hasRole('super_admin') === true;
    }
}
