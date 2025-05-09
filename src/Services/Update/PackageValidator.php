<?php

namespace LaravelUpdraft\Services\Update;

class PackageValidator
{
    /**
     * Validate that the package has the required structure
     *
     * @param string $extractPath Path to the extracted update package
     * @return bool True if valid, false otherwise
     */
    public function validatePackageStructure(string $extractPath): bool
    {
        $requiredPaths = [
            'files',
            'manifests',
            'update-manifest.json'
        ];

        $missingPaths = [];
        foreach ($requiredPaths as $path) {
            if (!file_exists($extractPath . '/' . $path)) {
                $missingPaths[] = $path;
            }
        }

        if (!empty($missingPaths)) {
            \Log::error('Invalid update package structure', [
                'extract_path' => $extractPath,
                'missing_paths' => $missingPaths,
                'existing_files' => glob($extractPath . '/*')
            ]);
            return false;
        }

        // Check if file-manifest.json exists as it's required for processing
        if (!file_exists($extractPath . '/manifests/file-manifest.json')) {
            \Log::error('Missing file-manifest.json in update package', [
                'extract_path' => $extractPath,
                'manifests_dir_contents' => file_exists($extractPath . '/manifests') ?
                    glob($extractPath . '/manifests/*') : 'manifests directory not found'
            ]);
            return false;
        }

        // Validate that the update-manifest.json is valid
        try {
            $manifest = json_decode(file_get_contents($extractPath . '/update-manifest.json'), true);
            if (!$manifest || !isset($manifest['version'])) {
                \Log::error('Invalid update-manifest.json in update package', [
                    'extract_path' => $extractPath
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to parse update-manifest.json', [
                'extract_path' => $extractPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if the update is compatible with the current installation
     * 
     * @param array $manifest The update manifest
     * @return bool|string Returns true if compatible, or error message if not
     */
    public function checkVersionCompatibility(array $manifest): bool|string
    {
        // Get the current application version
        $currentVersion = config('updraft.app.version', '0.0.0');

        // If app.version isn't set, log a warning
        if ($currentVersion === '0.0.0') {
            \Log::warning('updraft.app.version is not set in config. Using fallback version 0.0.0');
        }

        // Check PHP version
        if (isset($manifest['requiredPhpVersion']) && !version_compare(PHP_VERSION, $manifest['requiredPhpVersion'], '>=')) {
            return "PHP version {$manifest['requiredPhpVersion']} required, but current version is " . PHP_VERSION;
        }

        // Check Laravel version
        if (isset($manifest['requiredLaravelVersion']) && !version_compare(app()->version(), $manifest['requiredLaravelVersion'], '>=')) {
            return "Laravel version {$manifest['requiredLaravelVersion']} required, but current version is " . app()->version();
        }

        // Check minimum required app version
        if (isset($manifest['minimumRequiredVersion']) && !version_compare($currentVersion, $manifest['minimumRequiredVersion'], '>=')) {
            return "Application version {$manifest['minimumRequiredVersion']} required, but current version is {$currentVersion}";
        }

        return true;
    }
}