<?php

use App\Enums\ResponsibilityProfile;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Services\Responsibilities\AssetCharacteristicsBuilder;

beforeEach(function () {
    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->brand = Brand::create(['name' => 'HP', 'active' => true]);
});

test('a computer-profile asset produces exactly the MR LANA table rows, nothing more', function () {
    $assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP-T', 'active' => true, 'responsibility_profile' => ResponsibilityProfile::Computer]);

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $assetType->id,
        'brand_id' => $this->brand->id,
        'name' => 'Notebook HP EliteBook 845 G8',
        'model' => 'EliteBook 845 G8',
        'internal_code' => 'CML-LAP-001',
        'serial_number' => 'SN-001',
    ]);

    $rows = app(AssetCharacteristicsBuilder::class)->build($asset);

    expect(collect($rows)->pluck('name')->all())->toBe([
        'Equipo 1', 'Marca', 'Modelo', 'Procesador', 'Memoria RAM', 'Almacenamiento', 'Sistema operativo', 'Estado',
    ]);

    $byName = collect($rows)->keyBy('name');
    expect($byName['Equipo 1']['value'])->toBe('Notebook HP EliteBook 845 G8');
    expect($byName['Marca']['value'])->toBe('HP');
    expect($byName['Modelo']['value'])->toBe('EliteBook 845 G8');

    // Sin dato estructurado en el activo: vacío por diseño, nunca inventado.
    expect($byName['Procesador']['value'])->toBe('');
    expect($byName['Memoria RAM']['value'])->toBe('');
    expect($byName['Almacenamiento']['value'])->toBe('');
    expect($byName['Sistema operativo']['value'])->toBe('');
});

test('a computer-profile asset table never includes clave interna, tipo, numero de serie or empresa', function () {
    $assetType = AssetType::create(['name' => 'Equipo de cómputo', 'code' => 'EC-T', 'active' => true, 'responsibility_profile' => ResponsibilityProfile::Computer]);

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $assetType->id,
    ]);

    $names = collect(app(AssetCharacteristicsBuilder::class)->build($asset))->pluck('name');

    expect($names)->not->toContain('Clave interna', 'Tipo', 'Número de serie', 'Empresa');
});

test('a non-computer profile asset keeps the previous generic row structure', function () {
    $assetType = AssetType::create(['name' => 'Router', 'code' => 'ROU-T', 'active' => true, 'responsibility_profile' => ResponsibilityProfile::Network]);

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $assetType->id,
        'internal_code' => 'CML-ROU-001',
    ]);

    $names = collect(app(AssetCharacteristicsBuilder::class)->build($asset))->pluck('name')->all();

    expect($names)->toBe(['Equipo', 'Clave interna', 'Tipo', 'Marca', 'Modelo', 'Número de serie', 'Estado']);
});
