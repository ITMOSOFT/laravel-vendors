<?php

namespace ItmoSoft\Vendors;

use Illuminate\Support\ServiceProvider;

class VendorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vendors.php', 'itmosoft-vendors');
        $this->app->singleton(VendorManager::class, fn ($app) => new VendorManager(
            $app['config']->get('itmosoft-vendors', [])
        ));
        $this->app->alias(VendorManager::class, 'itmosoft.vendors');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vendors.php' => config_path('itmosoft-vendors.php'),
            ], 'itmosoft-vendors-config');
        }
    }
}
