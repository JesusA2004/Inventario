<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reportes/inventario/datos', [ReportController::class, 'inventoryData'])->name('reports.inventory-data');
    Route::get('reportes/{type}/excel', [ReportController::class, 'excel'])->name('reports.excel');
    Route::get('reportes/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});
