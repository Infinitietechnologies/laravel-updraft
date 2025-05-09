<?php

namespace LaravelUpdraft\Services\Update;

class UpdateProcessor
{
    /**
     * Process migrations from the update package
     *
     * @param string $extractPath Path to extracted update package
     * @throws \Exception When migrations fail
     */
    public function processMigrations(string $extractPath): void
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
     *
     * @param string $extractPath Path to extracted update package
     */
    public function processConfigUpdates(string $extractPath): void
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
     *
     * @param string $extractPath Path to extracted update package
     */
    public function runPostUpdateCommands(string $extractPath): void
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
     * Read the main manifest file
     *
     * @param string $extractPath Path to extracted update package
     * @return array Manifest data
     * @throws \Exception When manifest file is invalid
     */
    public function readMainManifest(string $extractPath): array
    {
        $manifestPath = $extractPath . '/update-manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest) {
            throw new \Exception('Invalid manifest file');
        }

        return $manifest;
    }
}