<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\AiTicket;
use App\Observers\ServiceRequestObserver;
use App\Observers\AiTicketObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ServiceRequest Observer রেজিস্টার করো
        ServiceRequest::observe(ServiceRequestObserver::class);
        // AiTicket Observer রেজিস্টার করো
        AiTicket::observe(AiTicketObserver::class);
    }
}
