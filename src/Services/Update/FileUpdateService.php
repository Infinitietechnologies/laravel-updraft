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
        $addedFiles = $this->getFilesFromManifest($fileManifest['added'], $extractPath);
        foreach ($addedFiles as $sourcePath => $destPath) {
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
                    'file' => basename($sourcePath),
                    'sourcePath' => $sourcePath
                ]);
            }
        }

        // Update modified files
        $modifiedFiles = $this->getFilesFromManifest($fileManifest['modified'], $extractPath);
        foreach ($modifiedFiles as $sourcePath => $destPath) {
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
                    'file' => basename($sourcePath),
                    'sourcePath' => $sourcePath
                ]);
            }
        }

        // Delete files
        foreach ($fileManifest['deleted'] as $file) {
            // Deleted files are still expected to be in array format
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
     * Get files from manifest, supporting both associative and simple array formats
     * 
     * @param array|object $manifestSection The section of the manifest (added, modified, etc)
     * @param string $extractPath Path to extracted update package
     * @return array Associative array of source => destination paths
     */
    protected function getFilesFromManifest($manifestSection, string $extractPath): array
    {
        $files = [];

        // Check if it's an associative array (new format)
        if (is_array($manifestSection) && array_keys($manifestSection) !== range(0, count($manifestSection) - 1)) {
            foreach ($manifestSection as $sourcePath => $destinationPath) {
                $files[$extractPath . '/' . $sourcePath] = base_path($destinationPath);
            }
        } 
        // Check if it's a simple array (old format)
        else if (is_array($manifestSection)) {
            foreach ($manifestSection as $file) {
                $files[$extractPath . '/files/' . $file] = base_path($file);
            }
        }

        return $files;
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

        // Ensure all required sections exist
        $manifest['added'] = $manifest['added'] ?? [];
        $manifest['modified'] = $manifest['modified'] ?? [];
        $manifest['deleted'] = $manifest['deleted'] ?? [];

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