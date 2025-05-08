<?php

use Illuminate\Support\Facades\Route;
use Espionage\ProjectUpdater\Http\Controllers\UpdateController;

Route::group(['prefix' => 'admin/updates', 'middleware' => config('project-updater.middleware')], function () {
    Route::get('/', [UpdateController::class, 'index'])->name('project-updater.index');
    Route::post('/upload', [UpdateController::class, 'upload'])->name('project-updater.upload');
});