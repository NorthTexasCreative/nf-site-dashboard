<?php

namespace App\Providers;

use App\Services\WpeApiClient;
use App\Services\Wpe\WpeInventorySyncService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WpeApiClient::class, fn () => WpeApiClient::fromConfig());
        $this->app->singleton(WpeInventorySyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
