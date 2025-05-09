<?php

namespace LaravelUpdraft\Services\Update;

use LaravelUpdraft\Models\UpdateHistory;
use Illuminate\Support\Facades\Auth;

class HistoryLogger
{
    /**
     * Log update information to the history table
     *
     * @param array $manifest Update manifest data
     * @param string|null $backupId ID of the backup created for this update
     * @param bool $successful Whether the update was successful
     * @param array $additionalMetadata Additional metadata to store
     * @return void
     */
    public function logUpdateHistory(array $manifest, ?string $backupId, bool $successful, array $additionalMetadata = []): void
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
}