<?php

namespace LaravelUpdraft;

use LaravelUpdraft\Models\UpdateHistory;
use Illuminate\Support\Facades\Auth;

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
     * @return bool|array Returns true on success, or error array on failure
     */
    public function processUpdate(string $updateFile): bool|array
    {
        $backupId = null;
        $manifest = null;
        $extractPath = null;
        
        try {
            // Extract the update package
            $extractPath = $this->extractUpdatePackage($updateFile);

            // Validate the update package structure
            if (!$this->validatePackageStructure($extractPath)) {
                throw new \Exception('Invalid update package structure. Package is missing required directories or files.', 1001);
            }

            // Read the main manifest
            $manifest = $this->readMainManifest($extractPath);

            // Check version compatibility
            $compatibilityCheck = $this->checkVersionCompatibility($manifest);
            if ($compatibilityCheck !== true) {
                throw new \Exception('Incompatible update version: ' . $compatibilityCheck, 1003);
            }
            
            // Check if this version is already applied
            if (UpdateHistory::hasVersion($manifest['version'])) {
                throw new \Exception('This update version has already been applied: ' . $manifest['version'], 1004);
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
            
            // Log successful update in the database
            $this->logUpdateHistory($manifest, $backupId, true);

            // Clean up the extracted files
            if ($extractPath && is_dir($extractPath)) {
                $this->cleanupExtractedFiles($extractPath);
            }

            return true;
        } catch (\Exception $e) {
            // Log the error with detailed information
            \Log::error('Update failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'updateFile' => $updateFile,
                'backupId' => $backupId ?? null
            ]);
            
            // If we have manifest information, log the failed update
            if (isset($manifest)) {
                $this->logUpdateHistory($manifest, $backupId ?? null, false, [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }

            // Attempt to restore from backup if available
            if (isset($backupId) && $backupId) {
                try {
                    $this->restoreFromBackup($backupId);
                    \Log::info("System restored from backup: {$backupId}");
                } catch (\Exception $restoreException) {
                    \Log::error("Failed to restore from backup: {$restoreException->getMessage()}");
                }
            }

            // Clean up the extracted files if they exist
            if ($extractPath && is_dir($extractPath)) {
                $this->cleanupExtractedFiles($extractPath);
            }

            return [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'backupRestored' => isset($backupId) && $backupId,
            ];
        }
    }
    
    /**
     * Log update information to the history table
     */
    protected function logUpdateHistory(array $manifest, ?string $backupId, bool $successful, array $additionalMetadata = []): void
    {
        // Prepare metadata
        $metadata = array_merge([
            'requiredPhpVersion' => $manifest['requiredPhpVersion'] ?? null,
            'requiredLaravelVersion' => $manifest['requiredLaravelVersion'] ?? null,
            'minimumRequiredVersion' => $manifest['minimumRequiredVersion'] ?? null,
        ], $additionalMetadata);
        
        // Determine the user who applied the update
        $appliedBy = null;
        if (Auth::check()) {
            $user = Auth::user();
            $appliedBy = $user->email ?? ($user->name ?? $user->id);
        }
        
        // Create update history record
        UpdateHistory::create([
            'version' => $manifest['version'],
            'name' => $manifest['name'],
            'description' => $manifest['description'] ?? null,
            'applied_by' => $appliedBy,
            'metadata' => $metadata,
            'applied_at' => now(),
            'successful' => $successful,
            'backup_id' => $backupId,
        ]);
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
     * 
     * @param array $manifest The update manifest
     * @return bool|string Returns true if compatible, or error message if not
     */
    protected function checkVersionCompatibility(array $manifest): bool|string
    {
        // Get the current application version
        $currentVersion = config('app.version', '0.0.0');

        // If app.version isn't set, log a warning
        if ($currentVersion === '0.0.0') {
            \Log::warning('app.version is not set in config. Using fallback version 0.0.0');
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

    /**
     * Check if a backup exists
     */
    public function backupExists(string $backupId): bool
    {
        $backupPath = $this->backupPath . '/' . $backupId;
        return is_dir($backupPath) && file_exists($backupPath . '/backup-info.json');
    }

    /**
     * Get backup information
     */
    public function getBackupInfo(string $backupId): array
    {
        $backupPath = $this->backupPath . '/' . $backupId;
        $infoPath = $backupPath . '/backup-info.json';
        
        if (!file_exists($infoPath)) {
            throw new \Exception('Backup information not found');
        }
        
        return json_decode(file_get_contents($infoPath), true) ?: [];
    }

    /**
     * Rollback to a specific backup
     */
    public function rollbackToBackup(string $backupId): bool
    {
        try {
            $backupPath = $this->backupPath . '/' . $backupId;
            
            if (!is_dir($backupPath)) {
                throw new \Exception('Backup not found');
            }
            
            // Create a safety backup of current state first
            $safetyBackupId = 'safety_' . date('YmdHis') . '_' . uniqid();
            $currentVersion = config('app.version');
            $backupInfo = $this->getBackupInfo($backupId);
            
            // Create a full backup of the current application state
            // (simplified for brevity - in a real implementation this would be more comprehensive)
            $this->createSafetyBackup($safetyBackupId, $currentVersion, $backupInfo['version'] ?? 'unknown');
            
            // Restore files from backup
            $this->restoreFilesFromBackup($backupId);
            
            // Log the rollback in history
            $this->logRollbackHistory($backupId, $backupInfo, $safetyBackupId);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Rollback failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a safety backup before rollback
     */
    protected function createSafetyBackup(string $backupId, string $currentVersion, string $targetVersion): void
    {
        $backupPath = $this->backupPath . '/' . $backupId;
        
        // Create backup directory
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        // In a production implementation, this would make a comprehensive backup
        // of files that might be affected by the rollback
        
        // Save backup metadata
        file_put_contents(
            $backupPath . '/backup-info.json',
            json_encode([
                'timestamp' => time(),
                'version' => $currentVersion,
                'rollbackTo' => $targetVersion,
                'type' => 'safety_backup_before_rollback'
            ])
        );
    }
    
    /**
     * Restore files from a backup
     */
    protected function restoreFilesFromBackup(string $backupId): void
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
                
                // Create directory structure if needed
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                copy($item->getPathname(), $destPath);
            }
        }
    }
    
    /**
     * Log rollback in history
     */
    protected function logRollbackHistory(string $backupId, array $backupInfo, string $safetyBackupId): void
    {
        // Determine the user who applied the rollback
        $appliedBy = null;
        if (\Auth::check()) {
            $user = \Auth::user();
            $appliedBy = $user->email ?? ($user->name ?? $user->id);
        }
        
        // Create update history record for the rollback
        UpdateHistory::create([
            'version' => $backupInfo['version'] ?? 'Unknown',
            'name' => 'Rollback to backup: ' . $backupId,
            'description' => 'System rollback to a previous version',
            'applied_by' => $appliedBy,
            'metadata' => [
                'rollback' => true,
                'backupId' => $backupId,
                'safetyBackupId' => $safetyBackupId,
                'timestamp' => time()
            ],
            'applied_at' => now(),
            'successful' => true,
            'backup_id' => $safetyBackupId,
        ]);
    }
    
    /**
     * Clean up extracted files after update
     *
     * @param string $extractPath Path to clean up
     * @return void
     */
    protected function cleanupExtractedFiles(string $extractPath): void
    {
        if (!is_dir($extractPath)) {
            return;
        }
        
        // Use RecursiveDirectoryIterator to recursively delete all files and directories
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }
        
        // Remove the main directory
        rmdir($extractPath);
    }
}
