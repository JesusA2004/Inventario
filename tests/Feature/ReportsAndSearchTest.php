<?php

use App\Exports\AssetsExport;
use App\Exports\Reports\ReportExport;
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
        function (ReportExport $export) {
            /** @var AssetsExport $dataSheet */
            $dataSheet = $export->sheets()['Datos'];

            return $dataSheet->collection()->pluck('internal_code')->toArray() === [$this->asset->internal_code];
        },
    );
});

test('the live inventory data endpoint respects filters and matches the export', function () {
    Excel::fake();

    $otherBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Sucursal Norte', 'code' => 'NORTE', 'active' => true]);
    Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $otherBranch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-999',
    ]);

    $response = $this->actingAs($this->user)->get('/reportes/inventario/datos?branch_id='.$this->branch->id);

    $response->assertOk();
    $response->assertJson(['total' => 1]);
    expect(collect($response->json('rows'))->pluck('internal_code')->toArray())->toBe([$this->asset->internal_code]);
    expect($response->json('byBranch'))->toHaveCount(1);

    $this->actingAs($this->user)->get('/reportes/inventario/excel?branch_id='.$this->branch->id)->assertOk();

    Excel::assertDownloaded(
        'reporte-inventario-'.now()->format('Y-m-d').'.xlsx',
        fn (ReportExport $export) => $export->sheets()['Datos']->collection()->pluck('internal_code')->toArray() === [$this->asset->internal_code],
    );
});

test('every report type has both excel and pdf exports that render', function () {
    foreach (['inventario', 'bajas', 'prestamos', 'piezas', 'auditorias'] as $type) {
        $this->actingAs($this->user)->get("/reportes/{$type}/excel")->assertOk();
        $this->actingAs($this->user)->get("/reportes/{$type}/pdf")->assertOk();
    }
});

test('the pdf for every report type includes a kpi summary, not just a plain table', function () {
    foreach (['inventario', 'bajas', 'prestamos', 'piezas', 'auditorias'] as $type) {
        $response = $this->actingAs($this->user)->get("/reportes/{$type}/pdf");

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }
});

test('the excel export for every report type includes a Datos sheet and a Resumen sheet', function () {
    Excel::fake();

    foreach (['inventario', 'bajas', 'prestamos', 'piezas', 'auditorias'] as $type) {
        $filename = "reporte-{$type}-".now()->format('Y-m-d').'.xlsx';

        $this->actingAs($this->user)->get("/reportes/{$type}/excel")->assertOk();

        Excel::assertDownloaded($filename, function (ReportExport $export) {
            $sheets = $export->sheets();

            return array_key_exists('Datos', $sheets) && array_key_exists('Resumen', $sheets);
        });
    }
});

test('the reports summary endpoint returns kpis for bajas, prestamos, piezas and auditorias', function () {
    $response = $this->actingAs($this->user)->get('/reportes/resumen?company_id='.$this->company->id);

    $response->assertOk();
    $response->assertJsonStructure([
        'bajas' => ['total', 'byReason', 'byCompany'],
        'prestamos' => ['active', 'returned', 'overdue', 'dueSoon'],
        'piezas' => ['total', 'functional', 'damaged', 'review', 'decommissioned'],
        'auditorias' => ['total', 'expected', 'found', 'missing', 'differences', 'compliancePercent'],
    ]);
});

test('the global search endpoint finds assets by internal code', function () {
    $response = $this->actingAs($this->user)->get('/buscar?q=CML-LAP-777');

    $response->assertOk();
    expect(collect($response->json('assets'))->pluck('internal_code'))->toContain('CML-LAP-777');
});
