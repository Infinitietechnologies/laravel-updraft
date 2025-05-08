<?php

namespace LaravelUpdraft\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelUpdraft\UpdateService;
use LaravelUpdraft\Models\UpdateHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            'update_package' => 'required|file|mimes:zip|max:51200', // 50MB max (in kilobytes)
            'confirm_backup' => 'required|in:1,true,on,yes'
        ]);
        
        try {
            // Store the uploaded file
            $path = $request->file('update_package')->store('updates');
            $fullPath = Storage::path($path);
            
            // Log upload details
            Log::info('Processing update package upload', [
                'filename' => $request->file('update_package')->getClientOriginalName(),
                'size' => $request->file('update_package')->getSize(),
                'stored_path' => $fullPath
            ]);
            
            // Process the update
            $result = $updateService->processUpdate($fullPath);
            
            // Clean up the temporary file
            Storage::delete($path);
            
            if ($result) {
                $message = 'Update successfully applied!';
                
                // Check if the request is AJAX or FilePond
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true, 
                        'message' => $message
                    ]);
                }
                
                return redirect()
                    ->route('laravel-updraft.index')
                    ->with('success', $message);
            } else {
                $message = 'Update failed. Check the logs for more information.';
                
                // Check if the request is AJAX or FilePond
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false, 
                        'message' => $message
                    ], 500);
                }
                
                return redirect()
                    ->route('laravel-updraft.index')
                    ->with('error', $message);
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Update upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $message = 'Update failed: ' . $e->getMessage();
            
            // Check if the request is AJAX or FilePond
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => $message
                ], 500);
            }
            
            return redirect()
                ->route('laravel-updraft.index')
                ->with('error', $message);
        }
    }
    
    /**
     * Handle FilePond file upload process requests
     * This endpoint can be used with FilePond's server.process configuration
     */
    public function processFile(Request $request)
    {
        try {
            // FilePond sends the file as 'filepond' by default
            if (!$request->hasFile('update_package') && $request->hasFile('filepond')) {
                $file = $request->file('filepond');
                
                // Validate file
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }
                
                // Store as a temporary file
                $path = $file->store('tmp/updates');
                
                // Return the stored path as a reference
                return response($path);
            }
            
            return response('No valid file uploaded', 400);
        } catch (\Exception $e) {
            Log::error('FilePond process failed', [
                'error' => $e->getMessage()
            ]);
            return response($e->getMessage(), 500);
        }
    }
    
    /**
     * Handle FilePond revert action
     * Clean up temporary files when a user removes a file
     */
    public function revertFile(Request $request)
    {
        try {
            // The request body should contain the file reference (path)
            $fileRef = $request->getContent();
            
            // Only delete if it's in the temporary uploads directory
            if (strpos($fileRef, 'tmp/updates/') === 0) {
                Storage::delete($fileRef);
            }
            
            return response('', 200);
        } catch (\Exception $e) {
            Log::error('FilePond revert failed', [
                'error' => $e->getMessage()
            ]);
            return response('', 500);
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
    public function processRollback($backupId, Request $request, UpdateService $updateService)
    {
        try {
            // Check if backup exists
            if (!$updateService->backupExists($backupId)) {
                $message = 'Backup not found.';
                
                // Check if the request is AJAX
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 404);
                }
                
                return redirect()
                    ->route('laravel-updraft.rollback-options')
                    ->with('error', $message);
            }
            
            // Process the rollback
            $result = $updateService->rollbackToBackup($backupId);
            
            if ($result) {
                $message = 'Rollback successfully completed!';
                
                // Check if the request is AJAX
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                
                return redirect()
                    ->route('laravel-updraft.history')
                    ->with('success', $message);
            } else {
                $message = 'Rollback failed. Check the logs for more information.';
                
                // Check if the request is AJAX
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 500);
                }
                
                return redirect()
                    ->route('laravel-updraft.rollback-options')
                    ->with('error', $message);
            }
        } catch (\Exception $e) {
            $message = 'Rollback failed: ' . $e->getMessage();
            
            // Check if the request is AJAX
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()
                ->route('laravel-updraft.rollback-options')
                ->with('error', $message);
        }
    }
}
