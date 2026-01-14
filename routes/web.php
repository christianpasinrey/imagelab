<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

// Public gallery (landing page)
Route::get('/', [ImageController::class, 'gallery'])->name('home');

// Editor
Route::get('/editor/{image?}', [ImageController::class, 'index'])->name('editor');

// Image routes with rate limiting
Route::prefix('images')->name('images.')->group(function () {
    // Upload: max 10 per minute
    Route::post('/', [ImageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');

    // Read operations: max 60 per minute
    Route::get('{image}', [ImageController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('show');

    Route::get('{image}/versions', [ImageController::class, 'versions'])
        ->middleware('throttle:60,1')
        ->name('versions');

    // Update/Process: max 30 per minute
    Route::put('{image}', [ImageController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('update');

    Route::post('{image}/process', [ImageController::class, 'process'])
        ->middleware('throttle:30,1')
        ->name('process');

    // Download: max 20 per minute
    Route::post('{image}/download', [ImageController::class, 'download'])
        ->middleware('throttle:20,1')
        ->name('download');

    // Delete: max 10 per minute
    Route::delete('{image}', [ImageController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('destroy');

    // Publish/Unpublish: max 20 per minute
    Route::post('{image}/publish', [ImageController::class, 'publish'])
        ->middleware('throttle:20,1')
        ->name('publish');

    Route::post('{image}/unpublish', [ImageController::class, 'unpublish'])
        ->middleware('throttle:20,1')
        ->name('unpublish');
});

// Legal pages
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
