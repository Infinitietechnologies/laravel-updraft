<?php

namespace Espionage\LaravelUpdraft;

use Illuminate\Support\ServiceProvider;
use Espionage\LaravelUpdraft\Console\Commands\UpdateCommand;

class ProjectUpdaterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the update service
        $this->app->singleton(UpdateService::class, function ($app) {
            return new UpdateService(
                config('project-updater.update_path'),
                config('project-updater.backup_path')
            );
        });
        
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/project-updater.php', 'project-updater'
        );
    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/project-updater.php' => config_path('project-updater.php'),
        ], 'project-updater-config');
        
        // Load routes
        if (config('project-updater.web_interface', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'project-updater');
        
        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/project-updater'),
        ], 'project-updater-views');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateCommand::class,
            ]);
        }
    }
}