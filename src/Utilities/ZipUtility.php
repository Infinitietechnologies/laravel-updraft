<?php

namespace LaravelUpdraft\Utilities;

class ZipUtility
{
    /**
     * Extract a ZIP file to a specified directory
     *
     * @param string $zipFile Path to the ZIP file
     * @param string $extractPath Path to extract to
     * @return bool True if extraction was successful
     * @throws \Exception If extraction fails
     */
    public static function extract(string $zipFile, string $extractPath): bool
    {
        // Check if file exists
        if (!file_exists($zipFile)) {
            throw new \Exception("ZIP file does not exist: {$zipFile}");
        }

        // Extract ZIP file
        $zip = new \ZipArchive;
        $result = $zip->open($zipFile);

        if ($result === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            return true;
        } else {
            // Return a human-readable error message based on ZipArchive error codes
            $errorMessage = self::getZipErrorMessage($result);
            throw new \Exception("Could not open the update package: {$errorMessage}");
        }
    }
    
    /**
     * Get a human-readable error message for ZipArchive error codes
     *
     * @param int $code ZipArchive error code
     * @return string Human-readable error message
     */
    public static function getZipErrorMessage(int $code): string
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
}