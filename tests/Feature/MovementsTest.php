<?php

use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-777',
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');

    $this->asset->movements()->create([
        'user_id' => $this->user->id,
        'type' => MovementType::Alta,
        'comment' => 'Alta de activo en el inventario.',
    ]);
});

test('the global movements page lists movements across all assets', function () {
    $response = $this->actingAs($this->user)->get('/movimientos');

    $response->assertOk();
});

test('the movements list can be filtered by asset search and company', function () {
    $otherCompany = Company::create(['name' => 'MR INSIGHT', 'code' => 'MRI', 'active' => true]);
    $otherBranch = Branch::create(['company_id' => $otherCompany->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $otherAsset = Asset::factory()->create([
        'company_id' => $otherCompany->id,
        'branch_id' => $otherBranch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'MRI-LAP-001',
    ]);
    $otherAsset->movements()->create(['user_id' => $this->user->id, 'type' => MovementType::Alta]);

    $response = $this->actingAs($this->user)->get('/movimientos?company_id='.$this->company->id);

    $response->assertOk();
    $props = $response->viewData('page')['props'];
    $codes = collect($props['movements']['data'])->pluck('asset.internal_code');

    expect($codes)->toContain('CML-LAP-777');
    expect($codes)->not->toContain('MRI-LAP-001');
});
