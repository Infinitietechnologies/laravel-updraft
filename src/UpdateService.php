<?php

namespace LaravelUpdraft;

class UpdateService
{
    protected $updatePath;
    protected $backupPath;

    public function __construct($updatePath = null, $backupPath = null)
    {
        $this->updatePath = $updatePath ?: storage_path('app/updates');
        $this->backupPath = $backupPath ?: storage_path('app/backups');
    }

    /**
     * Process an uploaded update package
     * 
     * @param string $updateFile Path to the uploaded ZIP file
     * @return bool
     */
    public function processUpdate(string $updateFile): bool
    {
        try {
            // Extract the update package
            $extractPath = $this->extractUpdatePackage($updateFile);

            // Validate the update package structure
            if (!$this->validatePackageStructure($extractPath)) {
                throw new \Exception('Invalid update package structure');
            }

            // Read the main manifest
            $manifest = $this->readMainManifest($extractPath);

            // Check version compatibility
            if (!$this->checkVersionCompatibility($manifest)) {
                throw new \Exception('Incompatible update version');
            }

            // Create backup before applying updates
            $backupId = $this->createBackup($extractPath);

            // Process file changes
            $this->processFileChanges($extractPath);

            // Process migrations
            $this->processMigrations($extractPath);

            // Process config updates
            $this->processConfigUpdates($extractPath);

            // Run post-update commands
            $this->runPostUpdateCommands($extractPath);

            return true;
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Update failed: ' . $e->getMessage());

            // Attempt to restore from backup if available
            if (isset($backupId) && $backupId) {
                $this->restoreFromBackup($backupId);
            }

            return false;
        }
    }

    /**
     * Extract the update package to a temporary directory
     */
    protected function extractUpdatePackage(string $updateFile): string
    {
        $tempPath = $this->updatePath . '/' . uniqid('update_');

        // Create temp directory if it doesn't exist
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Extract ZIP file
        $zip = new \ZipArchive;
        if ($zip->open($updateFile) === true) {
            $zip->extractTo($tempPath);
            $zip->close();
            return $tempPath;
        }

        throw new \Exception('Could not open the update package');
    }

    /**
     * Validate that the package has the required structure
     */
    protected function validatePackageStructure(string $extractPath): bool
    {
        $requiredPaths = [
            'files',
            'manifests',
            'update-manifest.json'
        ];

        foreach ($requiredPaths as $path) {
            if (!file_exists($extractPath . '/' . $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read the main manifest file
     */
    protected function readMainManifest(string $extractPath): array
    {
        $manifestPath = $extractPath . '/update-manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest) {
            throw new \Exception('Invalid manifest file');
        }

        return $manifest;
    }

    /**
     * Check if the update is compatible with the current installation
     */
    protected function checkVersionCompatibility(array $manifest): bool
    {
        // Get the current application version
        $currentVersion = config('app.version');

        // Check PHP version
        if (isset($manifest['requiredPhpVersion']) && !version_compare(PHP_VERSION, $manifest['requiredPhpVersion'], '>=')) {
            return false;
        }

        // Check Laravel version
        if (isset($manifest['requiredLaravelVersion']) && !version_compare(app()->version(), $manifest['requiredLaravelVersion'], '>=')) {
            return false;
        }

        // Check minimum required app version
        if (isset($manifest['minimumRequiredVersion']) && !version_compare($currentVersion, $manifest['minimumRequiredVersion'], '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Create a backup of files that will be modified
     */
    protected function createBackup(string $extractPath): string
    {
        $backupId = date('YmdHis') . '_' . uniqid();
        $backupPath = $this->backupPath . '/' . $backupId;

        // Create backup directory
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Read file manifest
        $fileManifest = $this->readFileManifest($extractPath);

        // Backup files that will be modified
        foreach ($fileManifest['modified'] as $file) {
            $sourcePath = base_path($file);
            $destPath = $backupPath . '/' . $file;

            // Create directory structure
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Copy the file if it exists
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
            }
        }

        // Backup files that will be deleted
        foreach ($fileManifest['deleted'] as $file) {
            $sourcePath = base_path($file);
            $destPath = $backupPath . '/' . $file;

            if (file_exists($sourcePath)) {
                // Create directory structure
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                copy($sourcePath, $destPath);
            }
        }

        // Save backup metadata
        file_put_contents(
            $backupPath . '/backup-info.json',
            json_encode([
                'timestamp' => time(),
                'version' => config('app.version'),
                'updatedTo' => $this->readMainManifest($extractPath)['version']
            ])
        );

        return $backupId;
    }

    /**
     * Process file changes (added, modified, deleted)
     */
    protected function processFileChanges(string $extractPath): void
    {
        $fileManifest = $this->readFileManifest($extractPath);

        // Add new files
        foreach ($fileManifest['added'] as $file) {
            $sourcePath = $extractPath . '/files/' . $file;
            $destPath = base_path($file);

            // Create directory structure
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            copy($sourcePath, $destPath);
        }

        // Update modified files
        foreach ($fileManifest['modified'] as $file) {
            $sourcePath = $extractPath . '/files/' . $file;
            $destPath = base_path($file);

            copy($sourcePath, $destPath);
        }

        // Delete files
        foreach ($fileManifest['deleted'] as $file) {
            $path = base_path($file);

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Read the file manifest
     */
    protected function readFileManifest(string $extractPath): array
    {
        $manifestPath = $extractPath . '/manifests/file-manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest) {
            throw new \Exception('Invalid file manifest');
        }

        return $manifest;
    }

    /**
     * Process migrations from the update package
     */
    protected function processMigrations(string $extractPath): void
    {
        $manifestPath = $extractPath . '/manifests/migration-manifest.json';

        if (!file_exists($manifestPath)) {
            return; // No migrations to run
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (isset($manifest['migrations']) && count($manifest['migrations']) > 0) {
            // Copy migrations to the migrations directory
            foreach ($manifest['migrations'] as $migration) {
                $sourcePath = $extractPath . '/migrations/' . $migration;
                $destPath = database_path('migrations/' . $migration);

                copy($sourcePath, $destPath);
            }

            // Run the migrations
            \Artisan::call('migrate', ['--force' => true]);
        }
    }

    /**
     * Process config updates
     */
    protected function processConfigUpdates(string $extractPath): void
    {
        $manifestPath = $extractPath . '/manifests/config-manifest.json';

        if (!file_exists($manifestPath)) {
            return; // No config files to update
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (isset($manifest['configFiles']) && count($manifest['configFiles']) > 0) {
            foreach ($manifest['configFiles'] as $config) {
                $sourcePath = $extractPath . '/config/' . $config;
                $destPath = config_path($config);

                copy($sourcePath, $destPath);
            }
        }
    }

    /**
     * Run post-update commands
     */
    protected function runPostUpdateCommands(string $extractPath): void
    {
        $manifestPath = $extractPath . '/manifests/command-manifest.json';

        if (!file_exists($manifestPath)) {
            return; // No commands to run
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (isset($manifest['postUpdateCommands']) && count($manifest['postUpdateCommands']) > 0) {
            foreach ($manifest['postUpdateCommands'] as $command) {
                \Artisan::call($command);
            }
        }
    }

    /**
     * Restore from backup if update fails
     */
    protected function restoreFromBackup(string $backupId): void
    {
        $backupPath = $this->backupPath . '/' . $backupId;

        if (!is_dir($backupPath)) {
            throw new \Exception('Backup not found');
        }

        // Recursively restore all files from the backup
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $relativePath = str_replace($backupPath . '/', '', $item->getPathname());

                // Skip backup metadata
                if ($relativePath === 'backup-info.json') {
                    continue;
                }

                $destPath = base_path($relativePath);
                copy($item->getPathname(), $destPath);
            }
        }
    }
}
