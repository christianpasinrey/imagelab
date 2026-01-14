<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

// Public gallery (landing page)
Route::get('/', [ImageController::class, 'gallery'])->name('home');

// Editor
Route::get('/editor/{image?}', [ImageController::class, 'index'])->name('editor');

// Image routes
Route::prefix('images')->name('images.')->group(function () {
    Route::post('/', [ImageController::class, 'store'])->name('store');
    Route::get('{image}', [ImageController::class, 'show'])->name('show');
    Route::put('{image}', [ImageController::class, 'update'])->name('update');
    Route::post('{image}/process', [ImageController::class, 'process'])->name('process');
    Route::get('{image}/versions', [ImageController::class, 'versions'])->name('versions');
    Route::post('{image}/download', [ImageController::class, 'download'])->name('download');
    Route::delete('{image}', [ImageController::class, 'destroy'])->name('destroy');
});
