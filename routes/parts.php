<?php

use App\Http\Controllers\Parts\PartController;
use App\Http\Controllers\Parts\PartQrController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::resource('piezas', PartController::class)
        ->parameters(['piezas' => 'part'])
        ->names('parts')
        ->except(['destroy', 'show']);

    Route::post('piezas/{part}/baja', [PartController::class, 'decommission'])->name('parts.decommission');

    Route::get('piezas/{part}/qr', [PartQrController::class, 'show'])->name('parts.qr');
    Route::get('piezas/{part}/qr/descargar', [PartQrController::class, 'download'])->name('parts.qr.download');
});
