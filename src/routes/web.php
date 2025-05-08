<?php

use Illuminate\Support\Facades\Route;
use LaravelUpdraft\Http\Controllers\UpdateController;

Route::group(['prefix' => 'admin/updates', 'middleware' => config('laravel-updraft.middleware', ['web', 'auth'])], function () {
    // Main routes
    Route::get('/', [UpdateController::class, 'index'])->name('laravel-updraft.index');
    Route::post('/upload', [UpdateController::class, 'upload'])->name('laravel-updraft.upload');
    Route::get('/history', [UpdateController::class, 'history'])->name('laravel-updraft.history');
    
    // FilePond endpoints for file handling
    Route::post('/process-file', [UpdateController::class, 'processFile'])->name('laravel-updraft.process-file');
    Route::delete('/revert-file', [UpdateController::class, 'revertFile'])->name('laravel-updraft.revert-file');
    
    // Rollback routes
    Route::get('/rollback', [UpdateController::class, 'showRollbackOptions'])->name('laravel-updraft.rollback-options');
    Route::get('/rollback/{backupId}/confirm', [UpdateController::class, 'confirmRollback'])->name('laravel-updraft.confirm-rollback');
    Route::post('/rollback/{backupId}/process', [UpdateController::class, 'processRollback'])->name('laravel-updraft.process-rollback');
});
