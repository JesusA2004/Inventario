<?php

use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('ayuda', [HelpController::class, 'index'])->name('help.index');
});
