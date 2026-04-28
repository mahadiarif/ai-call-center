<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\LatestTicketsWidget;
use App\Filament\Widgets\WeeklyTicketsChartWidget;
use App\Filament\Widgets\LatestServiceRequestsWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->colors([
                'primary' => Color::Amber,
            ]) // 🚀 এই ব্র্যাকেটটা দিয়ে colors অ্যারেটা আগে বন্ধ করতে হবে
            ->brandName(function () {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('company_profiles')) {
                        $profile = \App\Models\CompanyProfile::where('is_active', true)->first();
                        return $profile ? $profile->company_name : 'Laravel';
                    }
                } catch (\Exception $e) {}
                return 'Laravel';
            })
            ->brandLogo(function () {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('company_profiles')) {
                        $profile = \App\Models\CompanyProfile::where('is_active', true)->first();
                        if ($profile && $profile->company_logo) {
                            $url = url('storage/' . $profile->company_logo);
                            return new \Illuminate\Support\HtmlString('<img src="' . e($url) . '" alt="" style="height:2rem;object-fit:contain;display:block;max-width:160px;" />');
                        }
                    }
                } catch (\Exception $e) {}
                return null;
            })
            ->brandLogoHeight('2rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget সরানো হয়েছে — dashboard এ gap তৈরি করছিল
            ])
            ->profile() // sidebar এ profile/sign-out দেখাবে
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