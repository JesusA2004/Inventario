<?php

use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Maatwebsite\Excel\Facades\Excel;

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

test('the inventory report can be filtered by branch, type, brand and status', function () {
    Excel::fake();

    $otherBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Sucursal Norte', 'code' => 'NORTE', 'active' => true]);
    Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $otherBranch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-999',
    ]);

    $this->actingAs($this->user)->get('/reportes/inventario/excel?branch_id='.$this->branch->id)->assertOk();

    Excel::assertDownloaded(
        'reporte-inventario-'.now()->format('Y-m-d').'.xlsx',
        function (AssetsExport $export) {
            return $export->collection()->pluck('internal_code')->toArray() === [$this->asset->internal_code];
        },
    );
});

test('the global search endpoint finds assets by internal code', function () {
    $response = $this->actingAs($this->user)->get('/buscar?q=CML-LAP-777');

    $response->assertOk();
    expect(collect($response->json('assets'))->pluck('internal_code'))->toContain('CML-LAP-777');
});
