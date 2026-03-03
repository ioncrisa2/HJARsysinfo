<?php

namespace App\Http\Responses\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Filament::auth()->user();

        if ($user && method_exists($user, 'hasRole') && (! $user->hasRole('super_admin'))) {
            return redirect()->to(route('home.dashboard'));
        }

        return redirect()->intended(Filament::getUrl());
    }
}
