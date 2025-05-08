<?php

namespace LaravelUpdraft;

use Illuminate\Support\ServiceProvider;
use LaravelUpdraft\Console\Commands\UpdateCommand;
use LaravelUpdraft\Console\Commands\RollbackCommand;
use LaravelUpdraft\Http\Middleware\LocaleMiddleware;
use Illuminate\Routing\Router;

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
            __DIR__ . '/config/laravel-updraft.php', 'laravel-updraft'
        );
    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('updraft.locale', LocaleMiddleware::class);
        
        // Get the middleware group for the package
        $updraftMiddleware = config('laravel-updraft.middleware', ['web', 'auth']);
        
        // Make sure the locale middleware is included
        if (!in_array('updraft.locale', $updraftMiddleware)) {
            $updraftMiddleware[] = 'updraft.locale';
        }
        
        // Update the config
        config(['laravel-updraft.middleware' => $updraftMiddleware]);
        
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/laravel-updraft.php' => config_path('laravel-updraft.php'),
        ], 'laravel-updraft-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/database/migrations' => database_path('migrations'),
        ], 'laravel-updraft-migrations');
        
        // Publish assets
        $this->publishes([
            __DIR__ . '/public/assets' => public_path('vendor/laravel-updraft/assets'),
        ], 'laravel-updraft-assets');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'laravel-updraft');
        
        // Publish translations
        $this->publishes([
            __DIR__ . '/resources/lang' => resource_path('lang/vendor/laravel-updraft'),
        ], 'laravel-updraft-translations');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        
        // Load routes
        if (config('laravel-updraft.web_interface', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'laravel-updraft');
        
        // Publish views
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/laravel-updraft'),
        ], 'laravel-updraft-views');

        // Register a view composer for all laravel-updraft views to provide asset paths
        view()->composer('laravel-updraft::*', function ($view) {
            // Check if the assets have been published to the vendor directory
            $assetPath = 'vendor/laravel-updraft/assets';
            
            // For development environment, if vendor assets don't exist, use direct path
            if (!file_exists(public_path($assetPath))) {
                $assetPath = 'assets';
            }
            
            $view->with('assetPath', $assetPath);
        });
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateCommand::class,
                RollbackCommand::class,
            ]);
        }
    }
}
