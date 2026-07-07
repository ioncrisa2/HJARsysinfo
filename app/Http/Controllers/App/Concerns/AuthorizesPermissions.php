<?php

namespace App\Http\Controllers\App\Concerns;

use Illuminate\Support\Facades\Gate;

trait AuthorizesPermissions
{
    protected function authorizePermission(string|array $permissions): void
    {
        Gate::authorize('app.permission', [$permissions]);
    }
}
