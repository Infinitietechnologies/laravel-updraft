<?php

namespace LaravelUpdraft\Services\Rollback;

use LaravelUpdraft\Models\UpdateHistory;
use LaravelUpdraft\Services\Backup\BackupService;
use LaravelUpdraft\Utilities\PathUtility;

class RollbackService
{
    protected $backupPath;
    protected $backupService;

    public function __construct($backupPath = null)
    {
        $this->backupPath = $backupPath ?: storage_path('app/backups');
        $this->backupService = new BackupService($this->backupPath);
    }

    /**
     * Rollback to a specific backup
     *
     * @param string $backupId Backup ID
     * @return bool True if rollback was successful
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
            $backupInfo = $this->backupService->getBackupInfo($backupId);

            // Create a safety backup
            $this->backupService->createSafetyBackup($safetyBackupId, $currentVersion, $backupInfo['version'] ?? 'unknown');

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
     * Restore files from a backup
     *
     * @param string $backupId Backup ID
     * @throws \Exception If backup is not found or restoration fails
     */
    public function restoreFilesFromBackup(string $backupId): void
    {
        $backupPath = $this->backupPath . '/' . $backupId;

        // Ensure paths are normalized
        $backupPath = PathUtility::normalizePath($backupPath);

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
                $destPath = PathUtility::normalizePath($destPath);

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
     *
     * @param string $backupId Backup ID
     * @param array $backupInfo Backup information
     * @param string $safetyBackupId Safety backup ID
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
}