<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ImageController::class, 'index'])->name('editor');

Route::prefix('images')->name('images.')->group(function () {
    Route::post('/', [ImageController::class, 'store'])->name('store');
    Route::get('{image}', [ImageController::class, 'show'])->name('show');
    Route::post('{image}/process', [ImageController::class, 'process'])->name('process');
    Route::get('{image}/versions', [ImageController::class, 'versions'])->name('versions');
    Route::post('{image}/download', [ImageController::class, 'download'])->name('download');
    Route::delete('{image}', [ImageController::class, 'destroy'])->name('destroy');
});
