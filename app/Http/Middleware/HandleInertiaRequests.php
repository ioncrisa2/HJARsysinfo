<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email']),
                'is_super_admin' => (bool) $request->user()?->hasRole('super_admin'),
                'permissions' => $request->user()?->getAllPermissions()->pluck('name')->values()->all() ?? [],
            ],
            'flash' => [
                'error' => fn (): ?string => $request->session()->get('error'),
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ];
    }
}
