<?php

namespace App\Providers\Filament;

use App\Filament\GalleryQuickCreatePlugin;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditAccount;
use App\Filament\Resources\Assessors\AssessorResource;
use App\Filament\Resources\CertificationSchemes\CertificationSchemeResource;
use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Filament\Resources\Tuks\TukResource;
use App\Models\SitePage;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JohnRivera7\FilamentWidgetGrid\FilamentWidgetGridPlugin;
use Leek\FilamentRightClick\FilamentRightClickPlugin;
use Martin6363\SidebarResize\SidebarResizePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditAccount::class)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: false)
            ->databaseTransactions()
            ->brandName(fn (): string => SitePage::identityContent()['brand_name'])
            ->brandLogo(fn (): string => filled($path = SitePage::identityContent()['logo_path']) ? Storage::disk('public')->url($path) : asset('admin-logo.png'))
            ->favicon(fn (): string => filled($path = SitePage::identityContent()['admin_favicon_path']) ? Storage::disk('public')->url($path) : asset('admin-favicon.png'))
            ->brandLogoHeight('44px')
            ->sidebarWidth('272px')
            ->sidebarFullyCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups(['Administrasi', 'Konten Website', 'Data Sertifikasi'])
            ->userMenuItems([Action::make('website')->label('Buka website')->icon('heroicon-o-arrow-top-right-on-square')->url(config('services.frontend.url', 'http://localhost:3000'))->openUrlInNewTab()])
            ->plugins([
                GalleryQuickCreatePlugin::make()->label('Tambah')->includes([CertificationSchemeResource::class, GalleryItemResource::class, TukResource::class, AssessorResource::class])->sortBy('navigation')->alwaysShowModal()->modalWidths('5xl'),
                SidebarResizePlugin::make()->minWidth(80)->maxWidth(440),
                FilamentRightClickPlugin::make(),
                FilamentWidgetGridPlugin::make()->columns(12)->templates(false)
                    ->canCustomize(fn (): bool => Gate::allows('manage-admin'))
                    ->canManageDefaults(fn (): bool => false)
                    ->canShareTemplates(fn (): bool => false)
                    ->canViewWidget(fn (string $widget): bool => Gate::allows('manage-admin') && in_array($widget, Dashboard::WIDGETS, true)),
            ])
            ->dragAndScroll()
            ->colors([
                'primary' => array_replace(Color::hex('#1576b5'), [600 => '#1576b5', 700 => '#004195']),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
