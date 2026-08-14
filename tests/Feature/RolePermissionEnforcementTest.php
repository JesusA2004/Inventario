<?php

use App\Enums\AuditStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Audit;
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
    ]);

    $this->superadmin = User::factory()->create();
    $this->superadmin->assignRole('superadministrador');

    $this->sistemas = User::factory()->create();
    $this->sistemas->assignRole('sistemas');

    $this->auditor = User::factory()->create();
    $this->auditor->assignRole('auditor');

    $this->consulta = User::factory()->create();
    $this->consulta->assignRole('consulta');
});

test('only roles with crear-activos can create an asset', function () {
    $this->actingAs($this->consulta)->post('/activos', [])->assertForbidden();
    $this->actingAs($this->auditor)->post('/activos', [])->assertForbidden();

    // Los roles con permiso llegan a la validación (no al 403), lo que confirma
    // que el middleware de permisos ya no los bloquea.
    $this->actingAs($this->sistemas)->post('/activos', [])->assertSessionHasErrors();
    $this->actingAs($this->superadmin)->post('/activos', [])->assertSessionHasErrors();
});

test('read-only roles cannot create catalog records, including responsible people', function () {
    $this->actingAs($this->consulta)->post('/responsables', [])->assertForbidden();
    $this->actingAs($this->auditor)->post('/responsables', [])->assertForbidden();
    $this->actingAs($this->consulta)->post('/marcas', [])->assertForbidden();
    $this->actingAs($this->consulta)->post('/sucursales', [])->assertForbidden();
    $this->actingAs($this->consulta)->post('/tipos-activo', [])->assertForbidden();
    $this->actingAs($this->consulta)->post('/areas', [])->assertForbidden();

    $this->actingAs($this->sistemas)->post('/responsables', [])->assertSessionHasErrors();
});

test('only roles with gestionar-prestamos can register a loan', function () {
    $this->actingAs($this->consulta)->post('/prestamos', [])->assertForbidden();

    $this->actingAs($this->auditor)->post('/prestamos', [])->assertForbidden();

    $this->actingAs($this->sistemas)->post('/prestamos', [])->assertSessionHasErrors();
});

test('the auditor role can manage audits but consulta cannot', function () {
    $this->actingAs($this->consulta)->post('/auditorias', [])->assertForbidden();

    $this->actingAs($this->auditor)->post('/auditorias', [])->assertSessionHasErrors();
});

test('only the superadministrador can manage users, roles and system settings', function () {
    $this->actingAs($this->sistemas)->get('/usuarios')->assertForbidden();
    $this->actingAs($this->auditor)->get('/usuarios')->assertForbidden();
    $this->actingAs($this->consulta)->get('/usuarios')->assertForbidden();

    $this->actingAs($this->sistemas)->get('/roles')->assertForbidden();
    $this->actingAs($this->sistemas)->get('/configuracion')->assertForbidden();

    $this->actingAs($this->superadmin)->get('/usuarios')->assertOk();
    $this->actingAs($this->superadmin)->get('/configuracion')->assertOk();
});

test('every role with ver-reportes can view the reports index', function () {
    foreach (['superadmin', 'sistemas', 'auditor', 'consulta'] as $key) {
        $this->actingAs($this->{$key})->get('/reportes')->assertOk();
    }
});

test('a role without editar-activos cannot change an asset responsible', function () {
    $this->actingAs($this->consulta)
        ->post("/activos/{$this->asset->public_id}/cambiar-responsable", [])
        ->assertForbidden();

    $this->actingAs($this->auditor)
        ->post("/activos/{$this->asset->public_id}/cambiar-responsable", [])
        ->assertForbidden();
});

test('a role without dar-de-baja-activos cannot decommission an asset', function () {
    $this->actingAs($this->auditor)
        ->post("/activos/{$this->asset->public_id}/baja", [])
        ->assertForbidden();
});

test('the scanner endpoints require the ver-activos permission', function () {
    $audit = Audit::create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'name' => 'Auditoría de prueba',
        'status' => AuditStatus::EnProgreso,
        'started_at' => now(),
        'created_by' => $this->auditor->id,
    ]);

    $this->actingAs($this->consulta)->get('/escanear?audit_id='.$audit->id)->assertOk();
    $this->actingAs($this->consulta)->get('/escanear/activo/'.$this->asset->public_id)->assertOk();
});
