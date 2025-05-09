<?php

namespace LaravelUpdraft\Services\Update;

class FileUpdateService
{
    /**
     * Process file changes (added, modified, deleted)
     * 
     * @param string $extractPath Path to extracted update package
     * @throws \Exception When source files are not found
     */
    public function processFileChanges(string $extractPath): void
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
        
        // Process vendor files if included in the update package
        if (isset($fileManifest['vendor']) && is_array($fileManifest['vendor']) && !empty($fileManifest['vendor'])) {
            $this->processVendorFiles($extractPath, $fileManifest['vendor'], $filesNotFound);
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
     * Process vendor files included in the update package
     * 
     * @param string $extractPath Path to extracted update package
     * @param array $vendorFiles List of vendor files to update
     * @param array &$filesNotFound Reference to array of files not found
     * @return void
     */
    protected function processVendorFiles(string $extractPath, array $vendorFiles, array &$filesNotFound): void
    {
        $vendorDir = base_path('vendor');
        
        // Check if vendor directory exists
        if (!is_dir($vendorDir)) {
            \Log::warning("Vendor directory not found, skipping vendor updates", [
                'vendor_path' => $vendorDir
            ]);
            return;
        }
        
        \Log::info("Processing vendor files update", [
            'file_count' => count($vendorFiles)
        ]);
        
        // Create a backup of the affected vendor files before updating
        $this->backupVendorFiles($vendorFiles, $vendorDir);
        
        // Create a composer.json info file to warn about vendor modifications
        $this->createVendorModificationWarningFile($vendorFiles);
        
        foreach ($vendorFiles as $file) {
            $sourcePath = $extractPath . '/files/vendor/' . $file;
            $destPath = $vendorDir . '/' . $file;
            
            // Create directory structure if it doesn't exist
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Check if source file exists before trying to copy
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
                \Log::debug("Updated vendor file", ['file' => $file]);
            } else {
                $filesNotFound[] = $sourcePath;
                \Log::error("Source vendor file not found", [
                    'file' => $file,
                    'sourcePath' => $sourcePath
                ]);
            }
        }
        
        // Log a warning about potential issues with composer
        \Log::warning("Vendor files have been modified directly. This may cause issues with future Composer operations.", [
            'modified_files_count' => count($vendorFiles)
        ]);
    }
    
    /**
     * Create a backup of the vendor files being modified
     * 
     * @param array $vendorFiles List of vendor files to backup
     * @param string $vendorDir Path to vendor directory
     * @return void
     */
    protected function backupVendorFiles(array $vendorFiles, string $vendorDir): void
    {
        $backupDir = storage_path('app/vendor-backups/' . date('Y-m-d-His'));
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        foreach ($vendorFiles as $file) {
            $sourcePath = $vendorDir . '/' . $file;
            $destPath = $backupDir . '/' . $file;
            
            if (file_exists($sourcePath)) {
                // Create directory structure for backup
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                copy($sourcePath, $destPath);
            }
        }
        
        // Create a manifest of backed up files
        $manifestData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'files' => $vendorFiles
        ];
        
        file_put_contents($backupDir . '/manifest.json', json_encode($manifestData, JSON_PRETTY_PRINT));
        
        \Log::info("Created backup of vendor files before modification", [
            'backup_path' => $backupDir,
            'file_count' => count($vendorFiles)
        ]);
    }
    
    /**
     * Create a file to track and warn about vendor modifications
     * 
     * @param array $vendorFiles List of modified vendor files
     * @return void
     */
    protected function createVendorModificationWarningFile(array $vendorFiles): void
    {
        $warningFilePath = base_path('vendor/UPDRAFT-MODIFIED-FILES.json');
        
        // Read existing file if it exists
        $existingData = [];
        if (file_exists($warningFilePath)) {
            $existingData = json_decode(file_get_contents($warningFilePath), true) ?: [];
        }
        
        // Add new modification entry
        $existingData[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'modified_files' => $vendorFiles,
            'warning' => 'These files were modified by Laravel Updraft and may be overwritten by Composer operations'
        ];
        
        file_put_contents($warningFilePath, json_encode($existingData, JSON_PRETTY_PRINT));
    }

    /**
     * Read the file manifest
     * 
     * @param string $extractPath Path to extracted update package
     * @return array File manifest
     * @throws \Exception When manifest is invalid
     */
    public function readFileManifest(string $extractPath): array
    {
        $manifestPath = $extractPath . '/manifests/file-manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest) {
            throw new \Exception('Invalid file manifest');
        }

        return $manifest;
    }

    /**
     * Clean up extracted files after update
     *
     * @param string $extractPath Path to clean up
     * @return void
     */
    public function cleanupExtractedFiles(string $extractPath): void
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