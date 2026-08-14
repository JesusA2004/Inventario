<?php

use App\Enums\AssetStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->branch2 = Branch::create(['company_id' => $this->company->id, 'name' => 'Sucursal Norte', 'code' => 'NORTE', 'active' => true]);
    $this->department = Department::create(['name' => 'Sistemas', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('responsible can be changed via the quick action and creates a movement', function () {
    $responsible = ResponsiblePerson::create(['company_id' => $this->company->id, 'full_name' => 'Jesús Arizmendi', 'active' => true]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $responsible->id,
    ])->assertRedirect();

    expect($this->asset->fresh()->current_responsible_id)->toBe($responsible->id);
    expect($this->asset->movements()->where('type', MovementType::CambioResponsable)->exists())->toBeTrue();
});

test('location can be changed via the quick action and creates a movement', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-ubicacion", [
        'branch_id' => $this->branch2->id,
        'department_id' => $this->department->id,
    ])->assertRedirect();

    $this->asset->refresh();
    expect($this->asset->branch_id)->toBe($this->branch2->id);
    expect($this->asset->department_id)->toBe($this->department->id);
    expect($this->asset->movements()->where('type', MovementType::CambioSucursal)->exists())->toBeTrue();
});

test('a review can be registered and updates last_reviewed_at', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/revision", [
        'reviewed_at' => now()->toDateString(),
        'physical_status' => 'Bueno',
        'location_ok' => true,
        'responsible_ok' => true,
    ])->assertRedirect();

    $this->asset->refresh();
    expect($this->asset->last_reviewed_at->toDateString())->toBe(now()->toDateString());
    expect($this->asset->reviews()->count())->toBe(1);
    expect($this->asset->movements()->where('type', MovementType::Revision)->exists())->toBeTrue();
});

test('an asset can be decommissioned and reactivated without losing history', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/baja", [
        'date' => now()->toDateString(),
        'reason' => 'obsoleto',
        'notes' => 'Equipo muy antiguo.',
    ])->assertRedirect();

    $this->asset->refresh();
    expect($this->asset->in_inventory)->toBeFalse();
    expect($this->asset->status)->toBe(AssetStatus::Baja);
    expect($this->asset->decommission_reason)->toBe('obsoleto');
    expect($this->asset->movements()->where('type', MovementType::Baja)->exists())->toBeTrue();

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/reactivar")->assertRedirect();

    $this->asset->refresh();
    expect($this->asset->in_inventory)->toBeTrue();
    expect($this->asset->status)->toBe(AssetStatus::Activo);
    expect($this->asset->movements()->where('type', MovementType::Reactivacion)->exists())->toBeTrue();
    expect($this->asset->movements()->count())->toBeGreaterThanOrEqual(2);
});
