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
        // Clear any lingering messages that might be causing issues
        session()->forget(['error', 'update_success', 'success']);
        
        return view('laravel-updraft::update-form');
    }

    /**
     * Display the update history
     */
    public function history()
    {
        // Clear any lingering error messages when viewing the history page
        if (session()->has('error')) {
            session()->forget('error');
        }
        if (session()->has('update_success')) {
            session()->forget('update_success');
        }
        
        $updates = UpdateHistory::orderBy('applied_at', 'desc')->paginate(10);
        return view('laravel-updraft::update-history', compact('updates'));
    }

    /**
     * Upload process handler with vendor modification check
     */
    public function upload(Request $request, UpdateService $updateService)
    {
        try {
            // Check if this is a FilePond submission (contains file reference instead of actual file)
            if ($request->has('update_package') && is_string($request->input('update_package')) && !$request->hasFile('update_package')) {
                $filePath = $request->input('update_package');
                
                // Security check - only allow references to our temporary files
                if (!Storage::exists($filePath) || strpos($filePath, 'tmp/updates/') !== 0) {
                    throw new \Exception('Invalid file reference');
                }
                
                // Get the full path to the file
                $fullPath = Storage::path($filePath);
                
                Log::info('Processing update from FilePond reference', [
                    'file_reference' => $filePath,
                    'full_path' => $fullPath
                ]);
                
                // Make sure we clean up the temp file later
                $shouldCleanupTemp = true;
            } else {
                // Traditional file upload handling
                $request->validate([
                    'update_package' => 'required|file|mimes:zip|max:51200', // 50MB max
                    'confirm_backup' => 'required|in:1,true,on,yes'
                ]);
                
                // Store the uploaded file
                $path = $request->file('update_package')->store('updates');
                $fullPath = Storage::path($path);
                $filePath = $path;  // Store for later cleanup
                
                Log::info('Processing update package upload', [
                    'filename' => $request->file('update_package')->getClientOriginalName(),
                    'size' => $request->file('update_package')->getSize(),
                    'stored_path' => $fullPath
                ]);
                
                $shouldCleanupTemp = true;
            }

            // Force vendor updates if explicitly specified
            $forceVendorUpdates = $request->boolean('force_vendor_updates', false);
            
            // Process the update
            $result = $updateService->processUpdate($fullPath, $forceVendorUpdates);

            // Check if this update contains vendor modifications that require confirmation
            if (is_array($result) && isset($result['vendor_update_warning']) && $result['vendor_update_warning']) {
                // Keep the file for the confirmation page
                return redirect()->route('laravel-updraft.confirm-vendor-update', [
                    'update_file' => $fullPath,
                    'vendor_files_count' => $result['vendor_files_count'] ?? 0
                ]);
            }
            
            // Clean up the temporary file if needed
            if (isset($shouldCleanupTemp) && $shouldCleanupTemp && isset($filePath)) {
                Storage::delete($filePath);
            }

            // Check if result is true (success) or an array (error details)
            if ($result === true) {
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
                // Result is an array with error details
                $error = is_array($result) ? $result['error'] : 'Update failed. Check the logs for more information.';
                $backupRestored = is_array($result) && isset($result['backupRestored']) && $result['backupRestored'];

                $message = $error;
                if ($backupRestored) {
                    $message .= ' Your system has been restored to the previous state.';
                }

                // Check if the request is AJAX or FilePond
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'error' => $error,
                        'backupRestored' => $backupRestored
                    ], 422);
                }

                return redirect()
                    ->route('laravel-updraft.index')
                    ->with('error', $message)
                    ->with('update_success', false); // Explicitly mark as failed
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
                ->with('error', $message)
                ->with('update_success', false); // Explicitly mark as failed
        }
    }

    /**
     * Show confirmation screen for vendor updates
     */
    public function confirmVendorUpdate(Request $request, UpdateService $updateService)
    {
        try {
            $updateFile = $request->query('update_file');
            
            if (empty($updateFile) || !file_exists($updateFile)) {
                throw new \Exception('Invalid or missing update file');
            }
            
            // Get information about vendor modifications
            $vendorInfo = $updateService->checkForVendorModifications($updateFile);
            
            if (!$vendorInfo || !isset($vendorInfo['has_vendor_files']) || !$vendorInfo['has_vendor_files']) {
                // No vendor files to confirm, redirect to normal update process
                return redirect()->route('laravel-updraft.index')
                    ->with('error', 'No vendor files found in update package.');
            }
            
            return view('laravel-updraft::vendor.confirm-vendor-update', [
                'name' => $vendorInfo['version'] ?? 'Unknown update',
                'version' => $vendorInfo['version'] ?? 'Unknown',
                'vendorFileCount' => $vendorInfo['count'] ?? 0,
                'vendorFiles' => $vendorInfo['files'] ?? [],
                'extractPath' => $vendorInfo['extractPath'] ?? null,
                'updateFile' => $updateFile,
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing vendor update confirmation', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('laravel-updraft.index')
                ->with('error', 'Error preparing vendor update confirmation: ' . $e->getMessage());
        }
    }
    
    /**
     * Process a confirmed vendor update
     */
    public function processVendorUpdate(Request $request, UpdateService $updateService)
    {
        try {
            $updateFile = $request->input('update_file');
            $extractPath = $request->input('extract_path');
            
            if (empty($updateFile) || !file_exists($updateFile)) {
                throw new \Exception('Invalid or missing update file');
            }
            
            // Process the update with forced vendor updates
            $result = $updateService->processUpdate($updateFile, true);
            
            // Delete the update file when done
            @unlink($updateFile);
            
            if ($result === true) {
                return redirect()->route('laravel-updraft.index')
                    ->with('success', 'Update with vendor modifications successfully applied!');
            } else {
                $error = is_array($result) ? $result['error'] : 'Update failed. Check the logs for more information.';
                $backupRestored = is_array($result) && isset($result['backupRestored']) && $result['backupRestored'];
                
                $message = $error;
                if ($backupRestored) {
                    $message .= ' Your system has been restored to the previous state.';
                }
                
                return redirect()->route('laravel-updraft.index')
                    ->with('error', $message)
                    ->with('update_success', false);
            }
        } catch (\Exception $e) {
            Log::error('Vendor update processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('laravel-updraft.index')
                ->with('error', 'Vendor update failed: ' . $e->getMessage())
                ->with('update_success', false);
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
            $fileField = 'filepond';
            if (!$request->hasFile($fileField) && $request->hasFile('update_package')) {
                $fileField = 'update_package';
            }
            
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);

                // Validate file
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                // Store as a temporary file
                $path = $file->store('tmp/updates');

                // Log the temporary file storage
                Log::info('Temporary update file stored', [
                    'original_name' => $file->getClientOriginalName(),
                    'temp_path' => $path
                ]);

                // Return the stored path as a reference - FilePond expects this format
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

    /**
     * Set the application locale and store it in the session
     */
    public function setLocale($locale)
    {
        // Validate locale against available translations
        $availableLocales = ['en', 'es'];
        
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'en');  // Default to app locale or English
        }
        
        // Store locale in session
        session(['locale' => $locale]);
        
        // Set locale for the current request
        app()->setLocale($locale);
        
        // Redirect back to previous page or default to index
        $previousUrl = url()->previous();
        $currentLocaleUrl = url()->current();
        
        // Don't redirect back to the language switch URL
        if (empty($previousUrl) || $previousUrl === $currentLocaleUrl) {
            return redirect()->route('laravel-updraft.index');
        }
        
        return redirect($previousUrl);
    }
}
