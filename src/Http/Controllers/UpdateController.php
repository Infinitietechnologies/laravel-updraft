<?php

namespace LaravelUpdraft\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelUpdraft\UpdateService;
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
}
