<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Widgets\Map;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Illuminate\Session\Middleware\StartSession;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Validation\Rules\Password as Pass;
use App\Filament\Components\CustomUpdatePassword;
use Z3d0X\FilamentLogger\Resources\ActivityResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\PembandingResource\Widgets\StatsOverview;
use EightCedars\FilamentInactivityGuard\FilamentInactivityGuardPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->topNavigation(fn (): bool =>
            auth()->check()
            && ! auth()->user()->hasRole('super_admin')
        )
            ->passwordReset()
            ->colors(['primary' => Color::Amber,])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([Pages\Dashboard::class,])
            ->widgets([
                Map::class,
                StatsOverview::class,
            ])
            ->resources([
                ActivityResource::class
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class,])
            ->plugins([
                FilamentShieldPlugin::make(),
                EasyFooterPlugin::make()->withFooterPosition('footer')->withLoadTime('Halaman ini dimuat dalam'),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        userMenuLabel: 'My Profile',
                        shouldRegisterNavigation: false,
                        navigationGroup: 'Settings',
                        hasAvatars: true,
                        slug: 'my-profile'
                    )
                    ->myProfileComponents([
                        'update_password' => CustomUpdatePassword::class,
                    ])
                    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('public'))
                    ->passwordUpdateRules(
                        rules: [Pass::default()->mixedCase()->uncompromised(3)],
                        requiresCurrentPassword: true,
                    )->enableTwoFactorAuthentication(
                        force: false,
                    )->enableBrowserSessions(condition: true),

                FilamentInactivityGuardPlugin::make()

            ]);
    }
}
