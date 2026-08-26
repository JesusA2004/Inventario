<?php

use App\Http\Controllers\Public\PrivacyNoticeController;
use App\Http\Controllers\Public\PublicAssetController;
use App\Http\Controllers\Public\PublicPartController;
use App\Http\Controllers\Public\PublicResponsibilitySigningController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/a/{asset}', [PublicAssetController::class, 'show'])->name('public.assets.show');
    Route::get('/p/{part}', [PublicPartController::class, 'show'])->name('public.parts.show');
});

Route::get('/aviso-de-privacidad', [PrivacyNoticeController::class, 'show'])->name('privacy-notice.show');

// Firma pública de responsivas: sin login, protegida por token de alta
// entropía (nunca solo por public_id) + rate limiting contra fuerza bruta.
Route::middleware(['throttle:30,1'])->prefix('firmar/responsiva')->name('public.responsibilities.')->group(function () {
    Route::get('/{responsibility}', [PublicResponsibilitySigningController::class, 'show'])->name('show');
    Route::get('/{responsibility}/pdf', [PublicResponsibilitySigningController::class, 'previewPdf'])->name('preview');
    Route::post('/{responsibility}', [PublicResponsibilitySigningController::class, 'sign'])
        ->middleware('throttle:10,1')
        ->name('sign');
});

// Descarga de la copia firmada tras el correo de confirmación: URL firmada
// y temporal (Laravel signed route), nunca un link público permanente.
Route::get('/firmar/responsiva/{responsibility}/descarga-firmada', [PublicResponsibilitySigningController::class, 'downloadSigned'])
    ->middleware('signed')
    ->name('public.responsibilities.download-signed');
