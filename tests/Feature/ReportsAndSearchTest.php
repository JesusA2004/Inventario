<?php

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
});

test('the reports index page renders and excel/pdf exports respond', function () {
    $this->actingAs($this->user)->get('/reportes')->assertOk();
    $this->actingAs($this->user)->get('/reportes/inventario/excel')->assertOk();
    $this->actingAs($this->user)->get('/reportes/inventario/pdf')->assertOk();
    $this->actingAs($this->user)->get('/reportes/bajas/excel')->assertOk();
    $this->actingAs($this->user)->get('/reportes/prestamos/pdf')->assertOk();
    $this->actingAs($this->user)->get('/reportes/piezas/pdf')->assertOk();
    $this->actingAs($this->user)->get('/reportes/auditorias/pdf')->assertOk();
});

test('the global search endpoint finds assets by internal code', function () {
    $response = $this->actingAs($this->user)->get('/buscar?q=CML-LAP-777');

    $response->assertOk();
    expect(collect($response->json('assets'))->pluck('internal_code'))->toContain('CML-LAP-777');
});
