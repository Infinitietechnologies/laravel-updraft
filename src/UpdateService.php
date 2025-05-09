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
        $this->updatePath = $updatePath ?: storage_path('app/private/temp/updates');
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
        $errors = [];

        try {
            // Clear any previous error messages that might be in the session
            if (session()->has('error')) {
                session()->forget('error');
            }
            if (session()->has('update_success')) {
                session()->forget('update_success');
            }

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
                $errorMessage = 'This update version has already been applied: ' . $manifest['version'];

                // Log this situation for debugging
                \Log::warning('Attempted to apply already applied update version', [
                    'version' => $manifest['version'],
                    'requestTime' => now()->toDateTimeString(),
                ]);

                // Make sure we have a session and add error message to it
                try {
                    if (!request()->hasSession() && !session()->isStarted()) {
                        // If we're not in a web context or session isn't started, start one
                        session()->start();
                    }

                    // Now set the session data
                    session()->flash('error', $errorMessage);
                    session()->flash('update_success', false);

                    // Log success of setting session data
                    \Log::info('Successfully set error session data', [
                        'session_id' => session()->getId(),
                        'has_error' => session()->has('error'),
                    ]);
                } catch (\Exception $e) {
                    // Log the session error but continue with the update process
                    \Log::warning('Failed to set session data for update error', [
                        'error' => $e->getMessage(),
                        'updateVersion' => $manifest['version']
                    ]);
                }

                throw new \Exception($errorMessage, 1004);
            }

            // Create backup before applying updates
            $backupId = $this->createBackup($extractPath);

            try {
                // Process file changes
                $this->processFileChanges($extractPath);

                // Process migrations
                $this->processMigrations($extractPath);

                // Process config updates
                $this->processConfigUpdates($extractPath);

                // Run post-update commands
                $this->runPostUpdateCommands($extractPath);
            } catch (\Exception $e) {
                // Capture any errors during the update process
                \Log::error('Error during update process steps', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                // Try to restore from backup
                if ($backupId) {
                    try {
                        $this->restoreFromBackup($backupId);
                        \Log::info("System restored from backup: {$backupId}");
                    } catch (\Exception $restoreException) {
                        \Log::error("Failed to restore from backup: {$restoreException->getMessage()}");
                    }
                }

                // Rethrow the original exception
                throw $e;
            }

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
                    $errors[] = "Failed to restore from backup: " . $restoreException->getMessage();
                }
            }

            // Clean up the extracted files if they exist
            if ($extractPath && is_dir($extractPath)) {
                $this->cleanupExtractedFiles($extractPath);
            }

            // Add all collected errors
            if (!empty($errors)) {
                $errorMessage = $e->getMessage() . "\nAdditional errors: " . implode(", ", $errors);
            } else {
                $errorMessage = $e->getMessage();
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'code' => $e->getCode(),
                'backupRestored' => isset($backupId) && $backupId && empty($errors),
                'message' => $errorMessage
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
        // Check if file exists
        if (!file_exists($updateFile)) {
            throw new \Exception("Update file does not exist: {$updateFile}");
        }

        // Create a unique temp directory
        $tempPath = $this->updatePath . '/' . uniqid('update_');

        // Ensure the parent directory exists
        $parentDir = dirname($this->updatePath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        // Create temp directory if it doesn't exist
        if (!is_dir($this->updatePath)) {
            mkdir($this->updatePath, 0755, true);
        }

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Log extraction attempt
        \Log::info("Attempting to extract update package", [
            'source' => $updateFile,
            'destination' => $tempPath
        ]);

        // Extract ZIP file
        $zip = new \ZipArchive;
        $result = $zip->open($updateFile);

        if ($result === true) {
            $zip->extractTo($tempPath);
            $zip->close();

            // Log successful extraction
            \Log::info("Successfully extracted update package", [
                'path' => $tempPath,
                'files_count' => count(glob($tempPath . '/*'))
            ]);

            return $tempPath;
        } else {
            // Return a human-readable error message based on ZipArchive error codes
            $errorMessage = $this->getZipErrorMessage($result);
            \Log::error("Failed to extract update package", [
                'error_code' => $result,
                'error_message' => $errorMessage
            ]);

            throw new \Exception("Could not open the update package: {$errorMessage}");
        }
    }

    /**
     * Get a human-readable error message for ZipArchive error codes
     */
    protected function getZipErrorMessage(int $code): string
    {
        $errors = [
            \ZipArchive::ER_MULTIDISK => 'Multi-disk ZIP archives not supported',
            \ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            \ZipArchive::ER_CLOSE => 'Closing ZIP archive failed',
            \ZipArchive::ER_SEEK => 'Seek error',
            \ZipArchive::ER_READ => 'Read error',
            \ZipArchive::ER_WRITE => 'Write error',
            \ZipArchive::ER_CRC => 'CRC error',
            \ZipArchive::ER_ZIPCLOSED => 'ZIP archive closed',
            \ZipArchive::ER_NOENT => 'No such file',
            \ZipArchive::ER_EXISTS => 'File already exists',
            \ZipArchive::ER_OPEN => 'Cannot open file',
            \ZipArchive::ER_TMPOPEN => 'Failed to create temporary file',
            \ZipArchive::ER_ZLIB => 'Zlib error',
            \ZipArchive::ER_MEMORY => 'Memory allocation failure',
            \ZipArchive::ER_CHANGED => 'Entry has been changed',
            \ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            \ZipArchive::ER_EOF => 'Premature EOF',
            \ZipArchive::ER_INVAL => 'Invalid argument',
            \ZipArchive::ER_NOZIP => 'Not a ZIP archive',
            \ZipArchive::ER_INTERNAL => 'Internal error',
            \ZipArchive::ER_INCONS => 'ZIP archive inconsistent',
            \ZipArchive::ER_REMOVE => 'Cannot remove file',
            \ZipArchive::ER_DELETED => 'Entry has been deleted',
        ];

        return $errors[$code] ?? "Unknown error code: {$code}";
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

        // Ensure paths are normalized to prevent duplicate base path issues
        $backupPath = $this->normalizePath($backupPath);

        // Log backup path for debugging
        \Log::info("Creating backup", [
            'backupId' => $backupId,
            'backupPath' => $backupPath
        ]);

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

            // Normalize destination path
            $destPath = $this->normalizePath($destPath);

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

            // Normalize destination path
            $destPath = $this->normalizePath($destPath);

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
     * 
     * @throws \Exception When source files are not found
     */
    protected function processFileChanges(string $extractPath): void
    {
        $fileManifest = $this->readFileManifest($extractPath);
        $filesNotFound = [];

        // Add new files
        foreach ($fileManifest['added'] as $file) {
            $sourcePath = $extractPath . '/files/' . $file;
            $destPath = base_path($file);

            // Create directory structure
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Check if source file exists before trying to copy
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
            } else {
                $filesNotFound[] = $sourcePath;
                \Log::error("Source file not found for addition", [
                    'file' => $file,
                    'sourcePath' => $sourcePath
                ]);
            }
        }

        // Update modified files
        foreach ($fileManifest['modified'] as $file) {
            $sourcePath = $extractPath . '/files/' . $file;
            $destPath = base_path($file);

            // Create directory structure if it doesn't exist
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Check if source file exists before trying to copy
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
            } else {
                $filesNotFound[] = $sourcePath;
                \Log::error("Source file not found for modification", [
                    'file' => $file,
                    'sourcePath' => $sourcePath
                ]);
            }
        }

        // Delete files
        foreach ($fileManifest['deleted'] as $file) {
            $path = base_path($file);

            if (file_exists($path)) {
                unlink($path);
            } else {
                \Log::warning("Attempting to delete a file that doesn't exist", [
                    'file' => $file,
                    'path' => $path
                ]);
            }
        }

        // If any files were not found, throw an exception to stop the update process
        if (!empty($filesNotFound)) {
            $message = count($filesNotFound) === 1
                ? "Source file not found: {$filesNotFound[0]}"
                : count($filesNotFound) . " source files not found: " . implode(", ", array_map(function ($path) {
                    return basename($path);
                }, $filesNotFound));

            throw new \Exception($message);
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

                // Create directory structure if needed
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                copy($sourcePath, $destPath);
            }

            // Run the migrations
            try {
                \Log::info("Running migrations from update package");
                $output = \Artisan::call('migrate', ['--force' => true]);
                \Log::info("Migrations completed", ['output' => $output]);
            } catch (\Exception $e) {
                \Log::error("Failed to run migrations", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                throw new \Exception("Failed to run migrations: " . $e->getMessage(), 0, $e);
            }
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
                try {
                    \Log::info("Executing post-update command: {$command}");
                    $result = \Artisan::call($command);
                    \Log::info("Command executed with result: {$result}");
                } catch (\Exception $e) {
                    \Log::error("Failed to execute command: {$command}", [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);

                    // Don't throw the exception, just log it and continue
                    // This allows the update to proceed even if a command fails
                }
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

        \Log::info("Starting restore from backup", ['backupId' => $backupId, 'backupPath' => $backupPath]);

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
                    try {
                        // Ensure parent directories are created recursively
                        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                            throw new \Exception("Failed to create directory: {$destDir}");
                        }

                        \Log::info("Created directory", ['path' => $destDir]);
                    } catch (\Exception $e) {
                        \Log::error("Error creating directory", [
                            'path' => $destDir,
                            'error' => $e->getMessage(),
                            'exists' => is_dir($destDir),
                            'parent_exists' => is_dir(dirname($destDir))
                        ]);
                        throw new \Exception("Failed to create directory {$destDir}: " . $e->getMessage());
                    }
                }

                try {
                    if (!copy($item->getPathname(), $destPath)) {
                        throw new \Exception("Failed to copy file from {$item->getPathname()} to {$destPath}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Error copying file", [
                        'source' => $item->getPathname(),
                        'destination' => $destPath,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Failed to copy file: " . $e->getMessage());
                }
            }
        }

        \Log::info("Finished restoring from backup", ['backupId' => $backupId]);
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

        // Ensure paths are normalized
        $backupPath = $this->normalizePath($backupPath);

        if (!is_dir($backupPath)) {
            throw new \Exception('Backup not found');
        }

        \Log::info("Starting restore from backup", ['backupId' => $backupId, 'backupPath' => $backupPath]);

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

                // Normalize the destination path to prevent duplicate path issues
                $destPath = $this->normalizePath($destPath);

                // Create directory structure if needed
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    try {
                        // Ensure parent directories are created recursively
                        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                            throw new \Exception("Failed to create directory: {$destDir}");
                        }

                        \Log::info("Created directory", ['path' => $destDir]);
                    } catch (\Exception $e) {
                        \Log::error("Error creating directory", [
                            'path' => $destDir,
                            'error' => $e->getMessage(),
                            'exists' => is_dir($destDir),
                            'parent_exists' => is_dir(dirname($destDir))
                        ]);
                        throw new \Exception("Failed to create directory {$destDir}: " . $e->getMessage());
                    }
                }

                try {
                    if (!copy($item->getPathname(), $destPath)) {
                        throw new \Exception("Failed to copy file from {$item->getPathname()} to {$destPath}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Error copying file", [
                        'source' => $item->getPathname(),
                        'destination' => $destPath,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Failed to copy file: " . $e->getMessage());
                }
            }
        }

        \Log::info("Finished restoring from backup", ['backupId' => $backupId]);
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

    /**
     * Normalize a path to prevent duplicate base paths
     * 
     * @param string $path The path to normalize
     * @return string The normalized path
     */
    protected function normalizePath(string $path): string
    {
        // Convert all slashes to the same format
        $path = str_replace('\\', '/', $path);

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
            'original' => $path,
            'normalized' => $path
        ]);

        return $path;
    }
}
