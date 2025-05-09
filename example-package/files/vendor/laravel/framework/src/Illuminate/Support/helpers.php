<?php

namespace Illuminate\Support;

/*
 * This is a modified Support helpers.php file for testing the vendor update functionality
 * in Laravel Updraft.
 */

/**
 * Modified support helper function.
 *
 * @param  mixed  $value
 * @return mixed
 */
function enhanced_value($value)
{
    // Modified by vendor update to include extra debugging
    $originalValue = $value;
    
    // Transform the value in some way
    if (is_callable($value) && ! is_string($value)) {
        $value = $value();
    }
    
    // Added logging for debugging purposes
    if (function_exists('logger')) {
        logger("Enhanced value transform: " . json_encode($originalValue) . " => " . json_encode($value));
    }
    
    return $value;
}

/**
 * A new helper function added by the vendor update.
 *
 * @return string
 */
function vendor_update_applied()
{
    return 'Laravel Updraft vendor modification - ' . date('Y-m-d H:i:s');
}