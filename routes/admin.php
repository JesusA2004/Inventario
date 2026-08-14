<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::resource('usuarios', UserController::class)
        ->names('users')
        ->only(['index', 'store', 'update']);
    Route::post('usuarios/{user}/estado', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('usuarios/{user}/restablecer-contrasena', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

    Route::get('configuracion', [SettingsController::class, 'edit'])->name('system-settings.edit');
    Route::put('configuracion', [SettingsController::class, 'update'])->name('system-settings.update');
});
