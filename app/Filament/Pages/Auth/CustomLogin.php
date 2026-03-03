<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Pages\Auth\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    /**
     * Use the full-width base layout so we can craft a bespoke hero screen.
     *
     * @var string
     */
    protected static string $layout = 'filament-panels::components.layout.base';

    /**
     * Point to our panel-specific Blade view.
     *
     * @var string
     */
    protected static string $view = 'filament.admin.pages.auth.login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();

            if ($user && method_exists($user, 'hasRole') && (! $user->hasRole('super_admin'))) {
                redirect()->to(route('home.dashboard'));

                return;
            }

            redirect()->intended(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }

    public function hasLogo(): bool
    {
        // We'll render our own brand lockup in the view.
        return false;
    }
}
