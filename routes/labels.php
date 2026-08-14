<?php

use App\Http\Controllers\Labels\LabelCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('etiquetas', [LabelCenterController::class, 'index'])->name('labels.index');
    Route::post('etiquetas/pdf', [LabelCenterController::class, 'pdf'])->name('labels.pdf');
});
