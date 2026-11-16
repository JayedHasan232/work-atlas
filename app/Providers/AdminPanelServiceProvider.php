<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The Filament admin panel at /admin, for maintainers only (User::canAccessPanel). It has no login page
 * of its own: a guest is sent to the site's login. Each module keeps its resources in
 * `app/Modules/<Module>/Filament/Resources`; they are read-only until a milestone adds an action.
 */
final class AdminPanelServiceProvider extends PanelProvider
{
    /** Modules with admin resources. */
    private const array MODULES = ['Taxonomy', 'Knowledge', 'AiPipeline', 'Community'];

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn (): string => __('admin.brand'))
            ->colors(['primary' => Color::Emerald])
            ->pages([Dashboard::class])
            ->widgets([AccountWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);

        foreach (self::MODULES as $module) {
            $panel->discoverResources(
                in: app_path("Modules/{$module}/Filament/Resources"),
                for: "App\\Modules\\{$module}\\Filament\\Resources",
            );
        }

        return $panel;
    }
}
