<?php

declare(strict_types=1);

namespace Telkins\Dag\Providers;

use Illuminate\Support\ServiceProvider;
use Telkins\Dag\Services\DagService;

class DagServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/laravel-dag-manager.php' => config_path('laravel-dag-manager.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-dag-manager.php', 'laravel-dag-manager');

        if (! class_exists('CreateDagEdgesTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../../database/migrations/create_dag_edges_table.php.stub' => database_path("/migrations/{$timestamp}_create_dag_edges_table.php"),
            ], 'migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(DagService::class, function ($app) {
            return new DagService(config('laravel-dag-manager'));
        });
    }
}
