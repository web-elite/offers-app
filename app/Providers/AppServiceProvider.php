<?php

namespace App\Providers;

use App\Models\Offer;
use App\Models\Provider;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

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
        Offer::observe(AuditObserver::class);
        Provider::observe(AuditObserver::class);
    }
}

