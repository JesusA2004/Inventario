<?php

use App\Enums\AssetStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function createAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    return $user;
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);
    $this->brand = Brand::create(['name' => 'Dell', 'active' => true]);
});

test('the asset search endpoint returns an initial listing when no query is typed yet', function () {
    $user = createAdmin();

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $response = $this->actingAs($user)->get('/activos/buscar');

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($asset->id);
});

test('the asset search endpoint still filters by query when one is typed', function () {
    $user = createAdmin();

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-321',
    ]);

    Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-999',
    ]);

    $response = $this->actingAs($user)->get('/activos/buscar?q=321');

    $response->assertOk();
    $codes = collect($response->json())->pluck('internal_code')->all();
    expect($codes)->toBe([$asset->internal_code]);
});

test('an asset can be created and gets a permanent public_id', function () {
    $user = createAdmin();

    $response = $this->actingAs($user)->post('/activos', [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'brand_id' => $this->brand->id,
        'name' => 'Laptop Dell OptiPlex',
        'internal_code' => 'CML-LAP-001',
        'status' => AssetStatus::Activo->value,
        'in_inventory' => true,
        'acquired_at' => now()->toDateString(),
    ]);

    $asset = Asset::query()->where('internal_code', 'CML-LAP-001')->firstOrFail();
    $response->assertRedirect("/activos/{$asset->public_id}");

    expect($asset->public_id)->not->toBeEmpty();
    expect($asset->movements()->where('type', MovementType::Alta)->exists())->toBeTrue();

    $originalPublicId = $asset->public_id;

    $this->actingAs($user)->put("/activos/{$asset->public_id}", [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'brand_id' => $this->brand->id,
        'name' => 'Laptop Dell OptiPlex (editada)',
        'internal_code' => 'CML-LAP-001',
        'status' => AssetStatus::Almacenado->value,
        'in_inventory' => true,
        'acquired_at' => now()->toDateString(),
    ])->assertRedirect();

    $asset->refresh();
    expect($asset->public_id)->toBe($originalPublicId);
    expect($asset->status)->toBe(AssetStatus::Almacenado);
    expect($asset->movements()->where('type', MovementType::CambioEstado)->exists())->toBeTrue();
});

test('internal codes must be unique', function () {
    $user = createAdmin();

    Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'internal_code' => 'CML-LAP-001',
    ]);

    $this->actingAs($user)->post('/activos', [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Otra laptop',
        'internal_code' => 'CML-LAP-001',
        'status' => AssetStatus::Activo->value,
        'acquired_at' => now()->toDateString(),
    ])->assertSessionHasErrors('internal_code');
});

test('generate code produces sequential, unique codes per company and type', function () {
    $user = createAdmin();

    $first = $this->actingAs($user)->post('/activos/generar-clave', [
        'company_id' => $this->company->id,
        'asset_type_id' => $this->assetType->id,
    ])->json('code');

    $second = $this->actingAs($user)->post('/activos/generar-clave', [
        'company_id' => $this->company->id,
        'asset_type_id' => $this->assetType->id,
    ])->json('code');

    expect($first)->toBe('CML-LAP-001');
    expect($second)->toBe('CML-LAP-002');
});

test('checking a duplicate serial number returns the matching asset', function () {
    $user = createAdmin();

    $existing = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'serial_number' => 'ABC123456',
    ]);

    $response = $this->actingAs($user)->get('/activos/verificar-serie?serial_number=ABC123456');

    $response->assertOk();
    expect($response->json('match.id'))->toBe($existing->id);
});

test('a responsible person can be changed and it is reflected on the asset', function () {
    $user = createAdmin();

    $responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Jesús Arizmendi',
        'active' => true,
    ]);

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->actingAs($user)->put("/activos/{$asset->public_id}", [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => $asset->name,
        'internal_code' => $asset->internal_code,
        'status' => $asset->status->value,
        'current_responsible_id' => $responsible->id,
        'acquired_at' => $asset->acquired_at->toDateString(),
    ])->assertRedirect();

    expect($asset->fresh()->current_responsible_id)->toBe($responsible->id);
});
