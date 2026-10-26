<?php

namespace Filafly\Rail\Tests\Fixtures;

use Filafly\Rail\RailPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\EncryptCookies;
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
            ->login()
            ->tenant(Team::class, slugAttribute: 'slug')
            ->databaseNotifications()
            ->pages([
                Dashboard::class,
            ])
            ->plugin(
                RailPlugin::make()
                    ->items([
                        NavigationItem::make('Documentation')
                            ->icon(Heroicon::OutlinedBookOpen)
                            ->url('https://filamentphp.com/docs', shouldOpenInNewTab: true),
                        NavigationItem::make('Hidden link')
                            ->url('https://example.com')
                            ->visible(false),
                    ]),
            )
            ->middleware([
                EncryptCookies::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
