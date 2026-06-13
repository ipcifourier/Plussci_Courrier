<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\MyProfilePage;
use App\Http\Middleware\EnforceInactivityTimeout;
use App\Http\Middleware\EnforcePasswordRotation;
use App\Http\Middleware\RefreshPermissionCache;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('PLUSS.CI')
            ->brandLogo(asset('images/Logo One_Health.png'))
            ->darkModeBrandLogo(asset('images/Logo One_Health.png'))
            ->brandLogoHeight('3rem')
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.components.pluss-banner')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => view('filament.components.pluss-login-hero')->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.components.pluss-theme-overrides')->render(),
            )
            ->login()
            ->userMenuItems([
                MenuItem::make()
                    ->label('Mon profil')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => MyProfilePage::getUrl()),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigationGroups([
                NavigationGroup::make()->label('Agenda'),
                NavigationGroup::make()->label('Courriers'),
                NavigationGroup::make()->label('GED'),
                NavigationGroup::make()->label('Taches et Collaboration'),
                NavigationGroup::make()->label('Administration'),
                NavigationGroup::make()->label('Audit et traçabilité'),
            ])
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                RefreshPermissionCache::class,
                EnforceInactivityTimeout::class,
                EnforcePasswordRotation::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
