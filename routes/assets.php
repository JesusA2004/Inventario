<?php

use App\Http\Controllers\Assets\AssetActionController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\AssetFileController;
use App\Http\Controllers\Assets\AssetLabelController;
use App\Http\Controllers\Assets\AssetQrController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('activos/verificar-serie', [AssetController::class, 'checkSerial'])->name('assets.check-serial');
    Route::post('activos/generar-clave', [AssetController::class, 'generateCode'])->name('assets.generate-code');
    Route::get('activos/buscar', [AssetController::class, 'search'])->name('assets.search');
    Route::get('activos/ids-filtrados', [AssetController::class, 'filteredIds'])->name('assets.filtered-ids');
    Route::get('activos-exportar', [AssetController::class, 'export'])->name('assets.export');
    Route::post('activos-qr-zip', [AssetController::class, 'qrZip'])->name('assets.qr-zip');

    Route::resource('activos', AssetController::class)
        ->parameters(['activos' => 'asset'])
        ->names('assets')
        ->except(['destroy']);

    Route::post('activos/{asset}/archivos', [AssetFileController::class, 'store'])->name('assets.files.store');
    Route::delete('activos/{asset}/archivos/{file}', [AssetFileController::class, 'destroy'])->name('assets.files.destroy');
    Route::get('activos/{asset}/archivos/{file}/descargar', [AssetFileController::class, 'download'])->name('assets.files.download');

    Route::get('activos/{asset}/qr', [AssetQrController::class, 'show'])->name('assets.qr');
    Route::get('activos/{asset}/qr/descargar', [AssetQrController::class, 'download'])->name('assets.qr.download');
    Route::get('activos/{asset}/etiqueta', [AssetLabelController::class, 'show'])->name('assets.label');

    Route::post('activos/{asset}/cambiar-responsable', [AssetActionController::class, 'changeResponsible'])->name('assets.change-responsible');
    Route::post('activos/{asset}/cambiar-ubicacion', [AssetActionController::class, 'changeLocation'])->name('assets.change-location');
    Route::post('activos/{asset}/revision', [AssetActionController::class, 'review'])->name('assets.review');
    Route::post('activos/{asset}/baja', [AssetActionController::class, 'decommission'])->name('assets.decommission');
    Route::post('activos/{asset}/reactivar', [AssetActionController::class, 'reactivate'])->name('assets.reactivate');
});
