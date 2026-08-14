<?php

use App\Http\Controllers\Catalogs\AssetTypeController;
use App\Http\Controllers\Catalogs\BranchController;
use App\Http\Controllers\Catalogs\BrandController;
use App\Http\Controllers\Catalogs\CompanyController;
use App\Http\Controllers\Catalogs\DepartmentController;
use App\Http\Controllers\Catalogs\ResponsiblePersonController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::resource('empresas', CompanyController::class)
        ->parameters(['empresas' => 'company'])
        ->names('companies')
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('sucursales', BranchController::class)
        ->parameters(['sucursales' => 'branch'])
        ->names('branches')
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('areas', DepartmentController::class)
        ->parameters(['areas' => 'department'])
        ->names('departments')
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('marcas', BrandController::class)
        ->parameters(['marcas' => 'brand'])
        ->names('brands')
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('tipos-activo', AssetTypeController::class)
        ->parameters(['tipos-activo' => 'asset_type'])
        ->names('asset-types')
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('responsables', ResponsiblePersonController::class)
        ->parameters(['responsables' => 'responsable'])
        ->names('responsible-people')
        ->only(['index', 'store', 'update', 'destroy']);
});
