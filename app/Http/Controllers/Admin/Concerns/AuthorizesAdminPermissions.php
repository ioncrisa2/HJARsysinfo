<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Gate;

trait AuthorizesAdminPermissions
{
    protected function authorizeAdmin(string|array $permissions): void
    {
        Gate::authorize('admin.permission', [$permissions]);
    }
}
