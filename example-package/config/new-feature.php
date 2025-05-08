<?php

return [
    /*
    |--------------------------------------------------------------------------
    | New Feature Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the new feature added in the update.
    |
    */

    'enabled' => true,
    
    'display_name' => 'New Feature Module',
    
    'options' => [
        'allow_guest_access' => false,
        'cache_duration' => 60, // minutes
        'max_items' => 50,
    ],
    
    'routes' => [
        'prefix' => 'features',
        'middleware' => ['web', 'auth'],
    ],
];