<?php

namespace LaravelUpdraft;

use LaravelUpdraft\Models\UpdateHistory;
use LaravelUpdraft\Services\Backup\BackupService;
use LaravelUpdraft\Services\Rollback\RollbackService;
use LaravelUpdraft\Services\Update\FileUpdateService;
use LaravelUpdraft\Services\Update\HistoryLogger;
use LaravelUpdraft\Services\Update\PackageValidator;
use LaravelUpdraft\Services\Update\UpdateProcessor;
use LaravelUpdraft\Utilities\PathUtility;
use LaravelUpdraft\Utilities\ZipUtility;

class UpdateService
{
    protected $updatePath;
    protected $backupPath;
    protected $backupService;
    protected $fileUpdateService;
    protected $packageValidator;
    protected $updateProcessor;
    protected $historyLogger;
    protected $rollbackService;

    public function __construct($updatePath = null, $backupPath = null)
    {
        $this->updatePath = $updatePath ?: storage_path('app/private/temp/updates');
        $this->backupPath = $backupPath ?: storage_path('app/backups');
        
        // Initialize service dependencies
        $this->backupService = new BackupService($this->backupPath);
        $this->fileUpdateService = new FileUpdateService();
        $this->packageValidator = new PackageValidator();
        $this->updateProcessor = new UpdateProcessor();
        $this->historyLogger = new HistoryLogger();
        $this->rollbackService = new RollbackService($this->backupPath);
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
            // Clear any previous messages that might be in the session
            if (session()->has('error')) {
                session()->forget('error');
            }
            if (session()->has('update_success')) {
                session()->forget('update_success');
            }
            if (session()->has('success')) {
                session()->forget('success');
            }

            // Extract the update package
            $extractPath = $this->extractUpdatePackage($updateFile);

            // Validate the update package structure
            if (!$this->packageValidator->validatePackageStructure($extractPath)) {
                throw new \Exception('Invalid update package structure. Package is missing required directories or files.', 1001);
            }

            // Read the main manifest
            $manifest = $this->updateProcessor->readMainManifest($extractPath);

            // Check version compatibility
            $compatibilityCheck = $this->packageValidator->checkVersionCompatibility($manifest);
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

                // Handle session messages
                $this->handleSessionMessages($errorMessage);

                throw new \Exception($errorMessage, 1004);
            }

            // Get file manifest for backup creation
            $fileManifest = $this->fileUpdateService->readFileManifest($extractPath);
            
            // Create backup before applying updates
            $backupId = $this->backupService->createBackup($extractPath, $fileManifest);

            try {
                // Process file changes
                $this->fileUpdateService->processFileChanges($extractPath);

                // Process migrations
                $this->updateProcessor->processMigrations($extractPath);

                // Process config updates
                $this->updateProcessor->processConfigUpdates($extractPath);

                // Run post-update commands
                $this->updateProcessor->runPostUpdateCommands($extractPath);
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
                        $this->rollbackService->restoreFilesFromBackup($backupId);
                        \Log::info("System restored from backup: {$backupId}");
                    } catch (\Exception $restoreException) {
                        \Log::error("Failed to restore from backup: {$restoreException->getMessage()}");
                    }
                }

                // Rethrow the original exception
                throw $e;
            }

            // Log successful update in the database
            $this->historyLogger->logUpdateHistory($manifest, $backupId, true);

            // Clean up the extracted files
            if ($extractPath && is_dir($extractPath)) {
                $this->fileUpdateService->cleanupExtractedFiles($extractPath);
            }

            // Set success message in session
            session()->flash('success', 'Update applied successfully.');

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
                $this->historyLogger->logUpdateHistory($manifest, $backupId ?? null, false, [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }

            // Attempt to restore from backup if available
            if (isset($backupId) && $backupId) {
                try {
                    $this->rollbackService->restoreFilesFromBackup($backupId);
                    \Log::info("System restored from backup: {$backupId}");
                } catch (\Exception $restoreException) {
                    \Log::error("Failed to restore from backup: {$restoreException->getMessage()}");
                    $errors[] = "Failed to restore from backup: " . $restoreException->getMessage();
                }
            }

            // Clean up the extracted files if they exist
            if ($extractPath && is_dir($extractPath)) {
                $this->fileUpdateService->cleanupExtractedFiles($extractPath);
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
     * Extract the update package to a temporary directory
     *
     * @param string $updateFile The path to the uploaded ZIP file
     * @return string The path to the extracted files
     * @throws \Exception If extraction fails
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

        // Extract ZIP file using our utility
        try {
            ZipUtility::extract($updateFile, $tempPath);
            
            // Log successful extraction
            \Log::info("Successfully extracted update package", [
                'path' => $tempPath,
                'files_count' => count(glob($tempPath . '/*'))
            ]);
            
            return $tempPath;
        } catch (\Exception $e) {
            \Log::error("Failed to extract update package", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle session messages for update errors
     * 
     * @param string $errorMessage Error message to store
     */
    private function handleSessionMessages(string $errorMessage): void
    {
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
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if a backup exists
     */
    public function backupExists(string $backupId): bool
    {
        return $this->backupService->backupExists($backupId);
    }

    /**
     * Get backup information
     */
    public function getBackupInfo(string $backupId): array
    {
        return $this->backupService->getBackupInfo($backupId);
    }

    /**
     * Rollback to a specific backup
     */
    public function rollbackToBackup(string $backupId): bool
    {
        return $this->rollbackService->rollbackToBackup($backupId);
    }
}
