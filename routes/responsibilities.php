<?php

use App\Http\Controllers\Admin\PrivacyNoticeController;
use App\Http\Controllers\Admin\ResponsibilitySettingsController;
use App\Http\Controllers\Admin\ResponsibilityTemplateController;
use App\Http\Controllers\Responsibilities\AssetResponsibilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::prefix('responsivas')->name('responsibilities.')->group(function () {
        Route::get('/', [AssetResponsibilityController::class, 'index'])->name('index');
        Route::get('/{responsibility}', [AssetResponsibilityController::class, 'show'])->name('show');
        Route::get('/{responsibility}/vista-previa', [AssetResponsibilityController::class, 'preview'])->name('preview');
        Route::patch('/{responsibility}/caracteristicas', [AssetResponsibilityController::class, 'updateCharacteristics'])->name('update-characteristics');
        Route::post('/{responsibility}/enviar', [AssetResponsibilityController::class, 'send'])->name('send');
        Route::post('/{responsibility}/reenviar', [AssetResponsibilityController::class, 'resend'])->name('resend');
        Route::post('/{responsibility}/cancelar', [AssetResponsibilityController::class, 'cancel'])->name('cancel');
        Route::get('/{responsibility}/pdf', [AssetResponsibilityController::class, 'downloadUnsignedPdf'])->name('pdf');
        Route::get('/{responsibility}/pdf-firmado', [AssetResponsibilityController::class, 'downloadSignedPdf'])->name('signed-pdf');
        Route::post('/{responsibility}/copia-fisica', [AssetResponsibilityController::class, 'uploadPhysicalCopy'])->name('upload-physical');
        Route::get('/{responsibility}/copia-fisica', [AssetResponsibilityController::class, 'downloadPhysicalCopy'])->name('physical-copy');
    });

    Route::get('configuracion/responsivas', [ResponsibilitySettingsController::class, 'edit'])->name('responsibility-settings.edit');
    Route::patch('configuracion/responsivas/{company}', [ResponsibilitySettingsController::class, 'update'])->name('responsibility-settings.update');

    Route::post('configuracion/responsivas/plantillas', [ResponsibilityTemplateController::class, 'store'])->name('responsibility-templates.store');
    Route::post('configuracion/responsivas/plantillas/{template}/aprobar', [ResponsibilityTemplateController::class, 'approve'])->name('responsibility-templates.approve');
    Route::post('configuracion/responsivas/plantillas/{template}/desactivar', [ResponsibilityTemplateController::class, 'deactivate'])->name('responsibility-templates.deactivate');

    Route::post('configuracion/responsivas/aviso-privacidad', [PrivacyNoticeController::class, 'store'])->name('privacy-notice-versions.store');
});
