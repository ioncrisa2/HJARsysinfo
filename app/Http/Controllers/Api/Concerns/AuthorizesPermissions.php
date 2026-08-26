<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Gate;

trait AuthorizesPermissions
{
    protected function authorizePermission(string|array $permissions): void
    {
        Gate::authorize('app.permission', [$permissions]);
    }
}
