<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Support\AppAccess;
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
                'can' => AppAccess::capabilityMap($request->user(), [
                    'search' => 'view_search',
                ]),
            ],
            'appMenu' => fn (): array => AppAccess::menuFor($request->user()),
            'flash' => [
                'error' => fn (): ?string => $request->session()->get('error'),
                'success' => fn (): ?string => $request->session()->get('success'),
                'duplicate' => fn (): ?array => $request->session()->get('duplicate'),
            ],
            'appSettings' => SystemSetting::getAll(),
        ];
    }
}
