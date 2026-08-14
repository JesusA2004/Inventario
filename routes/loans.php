<?php

use App\Http\Controllers\Loans\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('prestamos/empresas/{company}/responsables', [LoanController::class, 'responsiblesForCompany'])->name('loans.company-responsibles');

    Route::resource('prestamos', LoanController::class)
        ->names('loans')
        ->only(['index', 'create', 'store']);

    Route::post('prestamos/{loan}/devolver', [LoanController::class, 'returnLoan'])->name('loans.return');
    Route::post('prestamos/{loan}/cancelar', [LoanController::class, 'cancel'])->name('loans.cancel');
});
