<?php

namespace App\Support;

use App\Models\IntegrationKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IntegrationAccess
{
    public const PREFIX = 'hjar_int_';

    public const SCOPES = ['pembandings:read', 'pembandings:similar', 'locations:read', 'dictionaries:read'];

    public static function key(?Request $request = null): ?IntegrationKey
    {
        return ($request ?? request())->attributes->get('integration_key');
    }

    public static function routeScope(Request $request): ?string
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'consumer:')) {
                return explode(',', substr($middleware, strlen('consumer:')))[0];
            }
        }

        return null;
    }

    public static function authorize(string $scope, string $ability, mixed $arguments): void
    {
        if ($key = self::key()) {
            abort_unless(self::routeScope(request()) === $scope && $key->allows($scope), 403);

            return;
        }

        Gate::authorize($ability, $arguments);
    }
}
