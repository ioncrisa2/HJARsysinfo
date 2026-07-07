<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if ($intendedUrl !== null && $this->isApplicationUrl($request, $intendedUrl)) {
            return $intendedUrl;
        }

        return route('app.dashboard');
    }

    private function isApplicationUrl(Request $request, string $intendedUrl): bool
    {
        $path = parse_url($intendedUrl, PHP_URL_PATH);
        $host = parse_url($intendedUrl, PHP_URL_HOST);

        return is_string($path)
            && ($host === null || $host === $request->getHost())
            && ($path === '/app' || str_starts_with($path, '/app/'));
    }
}
