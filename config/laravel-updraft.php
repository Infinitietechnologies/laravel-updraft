<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Updraft Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Laravel Updraft package.
    |
    */

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
    | Verification
    |--------------------------------------------------------------------------
    |
    | Enable or disable verification of update packages.
    |
    */
    'verify_updates' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Update Public Key
    |--------------------------------------------------------------------------
    |
    | The public key used to verify update packages.
    |
    */
    'update_public_key' => env('UPDATE_PUBLIC_KEY'),
];
