<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthenticate;
use App\Http\Middleware\FilamentEncryptCookies;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->brandName('EBlog')
            ->favicon('/favicon.svg')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('View Storefront')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->sort(-1),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => new HtmlString('
                    <script>
                        document.addEventListener("livewire:init", () => {
                            if (window.Livewire) {
                                Livewire.hook("request", ({ fail }) => {
                                    fail(({ status, preventDefault }) => {
                                        if (status === 419) {
                                            preventDefault();
                                            window.location.reload();
                                        }
                                    });
                                });
                            }
                        });
                    </script>
                ')
            )
            ->middleware([
                FilamentEncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
            ]);
    }
}
