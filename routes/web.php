<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/datos', [DashboardController::class, 'data'])->name('dashboard.data');
});

require __DIR__.'/settings.php';
require __DIR__.'/catalogs.php';
require __DIR__.'/assets.php';
require __DIR__.'/labels.php';
require __DIR__.'/loans.php';
require __DIR__.'/parts.php';
require __DIR__.'/audits.php';
require __DIR__.'/movements.php';
require __DIR__.'/admin.php';
require __DIR__.'/reports.php';
require __DIR__.'/search.php';
require __DIR__.'/public.php';
