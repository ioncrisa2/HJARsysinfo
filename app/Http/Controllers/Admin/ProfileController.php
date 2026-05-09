<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getPermissionNames()->values(),
                'created_at' => optional($user->created_at)->toDateTimeString(),
                'updated_at' => optional($user->updated_at)->toDateTimeString(),
            ],
            'activities' => $this->recentActivities($user->id),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return redirect()
            ->back()
            ->with('success', 'Profil admin berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return redirect()
            ->back()
            ->with('success', 'Password admin berhasil diperbarui.');
    }

    private function recentActivities(int $userId): array
    {
        return Activity::query()
            ->with('subject')
            ->where('causer_type', \App\Models\User::class)
            ->where('causer_id', $userId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'event' => $activity->event ?? $activity->description,
                'description' => $activity->description,
                'subject_type' => class_basename((string) $activity->subject_type),
                'subject_id' => $activity->subject_id,
                'created_at' => optional($activity->created_at)->toDateTimeString(),
            ])
            ->all();
    }
}
