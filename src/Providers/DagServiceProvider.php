<?php

namespace Telkins\Dag\Providers;

use Telkins\Dag\Services\DagService;
use Illuminate\Support\ServiceProvider;

class DagServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/laravel-dag-manager.php' => config_path('laravel-dag-manager.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-dag-manager.php', 'laravel-dag-manager');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(DagService::class, function ($app) {
            return new DagService(config('laravel-dag-manager'));
        });
    }
}
