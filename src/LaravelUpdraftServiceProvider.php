<?php

namespace LaravelUpdraft;

use Illuminate\Support\ServiceProvider;
use LaravelUpdraft\Console\Commands\UpdateCommand;

class LaravelUpdraftServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the update service
        $this->app->singleton(UpdateService::class, function ($app) {
            return new UpdateService(
                config('laravel-updraft.update_path'),
                config('laravel-updraft.backup_path')
            );
        });
        
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-updraft.php', 'laravel-updraft'
        );
    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/laravel-updraft.php' => config_path('laravel-updraft.php'),
        ], 'laravel-updraft-config');
        
        // Load routes
        if (config('laravel-updraft.web_interface', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-updraft');
        
        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-updraft'),
        ], 'laravel-updraft-views');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateCommand::class,
            ]);
        }
    }
}
