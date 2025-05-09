<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Updraft Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Updraft package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | App Version
    |--------------------------------------------------------------------------
    |
    | The current version of your application. This will be used to determine
    | compatibility with update packages.
    |
    */
    'app' => [
        'version' => env('APP_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Interface
    |--------------------------------------------------------------------------
    |
    | Enable or disable the web interface for the updater.
    |
    */
    'web_interface' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Web Routes Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to the web routes.
    |
    */
    'middleware' => ['web', 'auth', 'can:manage-updates'],
    
    /*
    |--------------------------------------------------------------------------
    | Update Package Path
    |--------------------------------------------------------------------------
    |
    | The path where update packages will be stored.
    |
    */
    'update_path' => storage_path('app/updates'),
    
    /*
    |--------------------------------------------------------------------------
    | Backup Path
    |--------------------------------------------------------------------------
    |
    | The path where backups will be stored.
    |
    */
    'backup_path' => storage_path('app/backups'),
    
    /*
    |--------------------------------------------------------------------------
    | Backup Retention
    |--------------------------------------------------------------------------
    |
    | The number of backups to keep. Set to 0 to keep all backups.
    |
    */
    'backup_retention' => 5,
    
    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The blade layout to use for the updraft views.
    | Default is 'layouts.app' which is common in Laravel applications.
    |
    */
    'layout' => 'updraft::layouts.app',
    'content'=> 'content',
];