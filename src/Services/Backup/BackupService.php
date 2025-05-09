<?php

namespace LaravelUpdraft\Services\Backup;

use LaravelUpdraft\Utilities\PathUtility;

class BackupService
{
    protected $backupPath;

    public function __construct($backupPath = null)
    {
        $this->backupPath = $backupPath ?: storage_path('app/backups');
    }

    /**
     * Create a backup of files that will be modified
     *
     * @param string $extractPath Path to extracted update package
     * @param array $fileManifest File manifest containing lists of files to modify
     * @return string Backup ID
     */
    public function createBackup(string $extractPath, array $fileManifest): string
    {
        $backupId = date('YmdHis') . '_' . uniqid();
        $backupPath = $this->backupPath . '/' . $backupId;

        // Ensure paths are normalized to prevent duplicate base path issues
        $backupPath = PathUtility::normalizePath($backupPath);

        // Log backup path for debugging
        \Log::info("Creating backup", [
            'backupId' => $backupId,
            'backupPath' => $backupPath
        ]);

        // Create backup directory
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Backup files that will be modified
        foreach ($fileManifest['modified'] as $file) {
            $this->backupFile($file, $backupPath);
        }

        // Backup files that will be deleted
        foreach ($fileManifest['deleted'] as $file) {
            $this->backupFile($file, $backupPath);
        }

        // Save backup metadata with manifest information
        $manifestData = json_decode(file_get_contents($extractPath . '/update-manifest.json'), true);
        
        file_put_contents(
            $backupPath . '/backup-info.json',
            json_encode([
                'timestamp' => time(),
                'version' => config('app.version'),
                'updatedTo' => $manifestData['version'] ?? 'unknown'
            ])
        );

        return $backupId;
    }

    /**
     * Backup a single file
     *
     * @param string $file Relative file path
     * @param string $backupPath Backup directory path
     * @return void
     */
    protected function backupFile(string $file, string $backupPath): void
    {
        $sourcePath = base_path($file);
        $destPath = $backupPath . '/' . $file;

        // Normalize destination path
        $destPath = PathUtility::normalizePath($destPath);

        // Only backup if file exists
        if (file_exists($sourcePath)) {
            // Create directory structure
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            copy($sourcePath, $destPath);
        }
    }

    /**
     * Create a safety backup before rollback
     *
     * @param string $backupId Backup ID
     * @param string $currentVersion Current version
     * @param string $targetVersion Target version to roll back to
     * @return void
     */
    public function createSafetyBackup(string $backupId, string $currentVersion, string $targetVersion): void
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
     * Check if a backup exists
     *
     * @param string $backupId Backup ID
     * @return bool True if backup exists
     */
    public function backupExists(string $backupId): bool
    {
        $backupPath = $this->backupPath . '/' . $backupId;
        return is_dir($backupPath) && file_exists($backupPath . '/backup-info.json');
    }

    /**
     * Get backup information
     *
     * @param string $backupId Backup ID
     * @return array Backup information
     * @throws \Exception If backup information is not found
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
}