<?php

namespace LaravelUpdraft\Utilities;

class PathUtility
{
    /**
     * Normalize a path to prevent duplicate base paths
     * 
     * @param string $path The path to normalize
     * @return string The normalized path
     */
    public static function normalizePath(string $path): string
    {
        // Store original path for logging
        $originalPath = $path;
        
        // Convert all backslashes to forward slashes for consistency
        $path = str_replace('\\', '/', $path);
        
        // Remove any double slashes that may have been created
        $path = preg_replace('#/+#', '/', $path);
        
        // Fix potential duplicate paths issue (e.g. "D:/projects/app/D:/projects/app/storage")
        $basePath = str_replace('\\', '/', base_path());
        if (strpos($path, $basePath . '/' . $basePath) === 0) {
            $path = preg_replace('~^' . preg_quote($basePath . '/' . $basePath, '~') . '~', $basePath, $path);
        }

        // Also check for storage path duplications
        $storagePath = str_replace('\\', '/', storage_path());
        if (strpos($path, $storagePath . '/' . $storagePath) === 0) {
            $path = preg_replace('~^' . preg_quote($storagePath . '/' . $storagePath, '~') . '~', $storagePath, $path);
        }

        // Log the normalized path to help diagnose issues
        \Log::debug("Path normalized", [
            'original' => $originalPath,
            'normalized' => $path
        ]);

        return $path;
    }
}