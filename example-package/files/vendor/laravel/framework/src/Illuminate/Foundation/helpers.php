<?php

namespace Illuminate\Foundation;

/*
 * This is a modified Foundation helpers.php file for testing the vendor update functionality
 * in Laravel Updraft.
 */

/**
 * Sample helper function that has been modified by the update.
 *
 * @param  string  $key
 * @param  mixed  $default
 * @return mixed
 */
function sample_helper($key = null, $default = null)
{
    // This is a modified version of the helper with additional functionality
    // MODIFIED BY LARAVEL UPDRAFT VENDOR UPDATE
    
    if (is_null($key)) {
        return app('config');
    }
    
    // Added debug logging functionality
    logger("Using sample_helper with key: " . $key);
    
    return app('config')->get($key, $default);
}

/**
 * A new helper function added by the vendor update.
 *
 * @return string
 */
function updraft_vendor_update_test()
{
    return 'Vendor update successfully applied via Laravel Updraft!';
}