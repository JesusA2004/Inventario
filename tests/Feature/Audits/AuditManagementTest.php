<?php

use App\Enums\AuditItemStatus;
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

    $this->assets = Asset::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('creating an audit seeds one pending item per matching asset', function () {
    $this->actingAs($this->user)->post('/auditorias', [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
    ])->assertRedirect();

    $audit = Audit::query()->firstOrFail();
    expect($audit->status)->toBe(AuditStatus::EnProgreso);
    expect($audit->items()->count())->toBe(3);
    expect($audit->items()->where('status', AuditItemStatus::Pendiente)->count())->toBe(3);
});

test('scanning an asset marks its audit item as found', function () {
    $audit = Audit::create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'name' => 'Auditoría de prueba',
        'started_at' => now(),
        'status' => AuditStatus::EnProgreso,
        'created_by' => $this->user->id,
    ]);

    $asset = $this->assets->first();
    $audit->items()->create(['asset_id' => $asset->id, 'status' => AuditItemStatus::Pendiente]);

    $this->actingAs($this->user)->post("/auditorias/{$audit->id}/marcar", [
        'asset_public_id' => $asset->public_id,
        'status' => AuditItemStatus::Encontrado->value,
    ])->assertRedirect();

    expect($audit->items()->where('asset_id', $asset->id)->first()->status)->toBe(AuditItemStatus::Encontrado);
});

test('finishing an audit marks pending items as not found', function () {
    $audit = Audit::create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'name' => 'Auditoría de prueba',
        'started_at' => now(),
        'status' => AuditStatus::EnProgreso,
        'created_by' => $this->user->id,
    ]);

    foreach ($this->assets as $asset) {
        $audit->items()->create(['asset_id' => $asset->id, 'status' => AuditItemStatus::Pendiente]);
    }

    $this->actingAs($this->user)->post("/auditorias/{$audit->id}/finalizar")->assertRedirect();

    $audit->refresh();
    expect($audit->status)->toBe(AuditStatus::Finalizada);
    expect($audit->items()->where('status', AuditItemStatus::NoEncontrado)->count())->toBe(3);
});

test('the scanner lookup endpoint resolves an asset by its public id', function () {
    $asset = $this->assets->first();

    $response = $this->actingAs($this->user)->get("/escanear/activo/{$asset->public_id}");

    $response->assertOk();
    expect($response->json('found'))->toBeTrue();
    expect($response->json('asset.internal_code'))->toBe($asset->internal_code);
});
