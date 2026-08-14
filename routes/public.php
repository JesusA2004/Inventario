<?php

use App\Http\Controllers\Public\PublicAssetController;
use App\Http\Controllers\Public\PublicPartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/a/{asset}', [PublicAssetController::class, 'show'])->name('public.assets.show');
    Route::get('/p/{part}', [PublicPartController::class, 'show'])->name('public.parts.show');
});
