<?php

use Illuminate\Support\Facades\Route;
use LaravelUpdraft\Http\Controllers\UpdateController;

// Apply the locale middleware to all routes
Route::group(['prefix' => 'admin/updates', 'middleware' => array_merge(config('updraft.middleware', ['web', 'auth']), ['updraft.locale'])], function () {
    // Main routes
    Route::get('/', [UpdateController::class, 'index'])->name('updraft.index');
    Route::post('/upload', [UpdateController::class, 'upload'])->name('updraft.upload');
    Route::get('/history', [UpdateController::class, 'history'])->name('updraft.history');
    
    // Vendor update confirmation routes
    Route::get('/confirm-vendor-update', [UpdateController::class, 'confirmVendorUpdate'])->name('updraft.confirm-vendor-update');
    Route::post('/process-vendor-update', [UpdateController::class, 'processVendorUpdate'])->name('updraft.process-vendor-update');
    
    // FilePond endpoints for file handling
    Route::post('/process-file', [UpdateController::class, 'processFile'])->name('updraft.process-file');
    Route::delete('/revert-file', [UpdateController::class, 'revertFile'])->name('updraft.revert-file');
    
    // Rollback routes - temporarily disabled
    // Route::get('/rollback', [UpdateController::class, 'showRollbackOptions'])->name('updraft.rollback-options');
    // Route::get('/rollback/{backupId}/confirm', [UpdateController::class, 'confirmRollback'])->name('updraft.confirm-rollback');
    // Route::post('/rollback/{backupId}/process', [UpdateController::class, 'processRollback'])->name('updraft.process-rollback');
    
    // Language switcher
    Route::get('/set-locale/{locale}', [UpdateController::class, 'setLocale'])->name('updraft.set-locale');
});
