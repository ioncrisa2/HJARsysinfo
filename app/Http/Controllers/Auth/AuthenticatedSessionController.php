<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->redirectPath($request));
        }

        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()?->deactivated_at !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan.',
            ]);
        }

        return redirect()->to($this->redirectPath(
            $request,
            $request->session()->pull('url.intended')
        ));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('login'));
    }

    private function redirectPath(Request $request, ?string $intendedUrl = null): string
    {
        if ($request->user()?->hasRole('super_admin')) {
            if ($this->intendedPathStartsWith($intendedUrl, '/admin')) {
                return $intendedUrl;
            }

            return route('admin.dashboard');
        }

        if ($intendedUrl !== null && ! $this->intendedPathStartsWith($intendedUrl, '/admin')) {
            return $intendedUrl;
        }

        return route('home.dashboard');
    }

    private function intendedPathStartsWith(?string $intendedUrl, string $prefix): bool
    {
        if ($intendedUrl === null || $intendedUrl === '') {
            return false;
        }

        $path = parse_url($intendedUrl, PHP_URL_PATH);

        return is_string($path) && Str::startsWith($path, $prefix);
    }
}
