<?php

namespace App\Http\Middleware;

use App\Support\IntegrationAccess;
use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDataConsumer
{
    public function handle(Request $request, Closure $next, string $scope, ?string $permission = null): Response
    {
        if ($key = IntegrationAccess::key($request)) {
            abort_unless($key->allows($scope), 403);

            return $next($request);
        }

        $middleware = [Authenticate::class.':sanctum', EnsureAppUser::class];
        if ($permission) {
            $middleware[] = PermissionMiddleware::class.':'.$permission;
        }

        return app(Pipeline::class)->send($request)->through($middleware)->then($next);
    }
}
