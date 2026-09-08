<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Support\IntegrationAccess;
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

            return response()->json([
                'status' => 'error',
                'code' => 'MAINTENANCE_MODE',
                'message' => 'Sistem sedang dalam pemeliharaan.',
                'errors' => null,
            ], 503);
        }

        return $next($request);
    }

    private function isAllowedDuringRestrictedMode(Request $request): bool
    {
        if (IntegrationAccess::key($request)) {
            return false;
        }

        if (
            $request->is('api/auth/*')
            || $request->is('api/v1/auth/*')
            || $request->is('sanctum/*')
            || $request->is('docs*')
            || $request->is('up')
        ) {
            return true;
        }

        return $request->user()?->hasRole('super_admin') === true;
    }
}
