<?php

use Illuminate\Support\Facades\Route;
use LaravelUpdraft\Http\Controllers\UpdateController;

Route::group(['prefix' => 'admin/updates', 'middleware' => config('laravel-updraft.middleware')], function () {
    Route::get('/', [UpdateController::class, 'index'])->name('laravel-updraft.index');
    Route::post('/upload', [UpdateController::class, 'upload'])->name('laravel-updraft.upload');
});
