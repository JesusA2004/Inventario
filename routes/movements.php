<?php

use App\Http\Controllers\MovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('movimientos', [MovementController::class, 'index'])->name('movements.index');
});
