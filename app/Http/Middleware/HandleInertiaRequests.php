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
            'notifications' => fn (): array => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'items' => $request->user()->unreadNotifications()->latest()->limit(5)->get()->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'message' => $notification->data['message'] ?? 'Notifikasi aplikasi',
                    'export_run_id' => $notification->data['export_run_id'] ?? null,
                    'status' => $notification->data['status'] ?? null,
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])->all(),
            ] : ['unread_count' => 0, 'items' => []],
            'flash' => [
                'error' => fn (): ?string => $request->session()->get('error'),
                'success' => fn (): ?string => $request->session()->get('success'),
                'duplicate' => fn (): ?array => $request->session()->get('duplicate'),
            ],
            'appSettings' => SystemSetting::getAll(),
        ];
    }
}
