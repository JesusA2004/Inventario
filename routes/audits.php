<?php

use App\Http\Controllers\Audits\AuditController;
use App\Http\Controllers\Audits\AuditItemController;
use App\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::resource('auditorias', AuditController::class)
        ->parameters(['auditorias' => 'audit'])
        ->names('audits')
        ->except(['destroy', 'edit', 'update']);

    Route::post('auditorias/{audit}/finalizar', [AuditController::class, 'finish'])->name('audits.finish');
    Route::post('auditorias/{audit}/marcar', [AuditItemController::class, 'mark'])->name('audits.mark');

    Route::get('escanear/activo/{publicId}', [ScannerController::class, 'lookup'])->name('scanner.lookup');
    Route::get('escanear', [ScannerController::class, 'index'])->name('scanner.index');
});
