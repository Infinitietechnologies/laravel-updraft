<?php

namespace LaravelUpdraft\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelUpdraft\UpdateService;
use LaravelUpdraft\Models\UpdateHistory;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    /**
     * Display the update form
     */
    public function index()
    {
        return view('laravel-updraft::update-form');
    }
    
    /**
     * Display the update history
     */
    public function history()
    {
        $updates = UpdateHistory::orderBy('applied_at', 'desc')->paginate(10);
        return view('laravel-updraft::update-history', compact('updates'));
    }
    
    /**
     * Process the update package upload
     */
    public function upload(Request $request, UpdateService $updateService)
    {
        // Validate the uploaded file
        $request->validate([
            'update_package' => 'required|file|mimes:zip|max:50000', // 50MB max
        ]);
        
        try {
            // Store the uploaded file
            $path = $request->file('update_package')->store('updates');
            $fullPath = Storage::path($path);
            
            // Process the update
            $result = $updateService->processUpdate($fullPath);
            
            if ($result) {
                // Clean up the temporary file
                Storage::delete($path);
                
                return redirect()
                    ->route('laravel-updraft.index')
                    ->with('success', 'Update successfully applied!');
            } else {
                return redirect()
                    ->route('laravel-updraft.index')
                    ->with('error', 'Update failed. Check the logs for more information.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('laravel-updraft.index')
                ->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the available backups for rollback
     */
    public function showRollbackOptions()
    {
        // Get all successful updates with backups
        $updates = UpdateHistory::where('successful', true)
            ->whereNotNull('backup_id')
            ->orderBy('applied_at', 'desc')
            ->paginate(10);
        
        return view('laravel-updraft::rollback-options', compact('updates'));
    }
    
    /**
     * Show confirmation screen before rollback
     */
    public function confirmRollback($backupId, UpdateService $updateService)
    {
        try {
            // Check if backup exists
            if (!$updateService->backupExists($backupId)) {
                return redirect()
                    ->route('laravel-updraft.rollback-options')
                    ->with('error', 'Backup not found.');
            }
            
            // Get the backup info
            $backupInfo = $updateService->getBackupInfo($backupId);
            
            // Get the update record
            $update = UpdateHistory::where('backup_id', $backupId)->first();
            
            return view('laravel-updraft::confirm-rollback', compact('backupId', 'backupInfo', 'update'));
        } catch (\Exception $e) {
            return redirect()
                ->route('laravel-updraft.rollback-options')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Process the rollback
     */
    public function processRollback($backupId, UpdateService $updateService)
    {
        try {
            // Check if backup exists
            if (!$updateService->backupExists($backupId)) {
                return redirect()
                    ->route('laravel-updraft.rollback-options')
                    ->with('error', 'Backup not found.');
            }
            
            // Process the rollback
            $result = $updateService->rollbackToBackup($backupId);
            
            if ($result) {
                return redirect()
                    ->route('laravel-updraft.history')
                    ->with('success', 'Rollback successfully completed!');
            } else {
                return redirect()
                    ->route('laravel-updraft.rollback-options')
                    ->with('error', 'Rollback failed. Check the logs for more information.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('laravel-updraft.rollback-options')
                ->with('error', 'Rollback failed: ' . $e->getMessage());
        }
    }
}
