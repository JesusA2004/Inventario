<?php

use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Angélica Libreros Sandoval',
        'active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

function updateResponsiblePayload(ResponsiblePerson $responsible, bool $active): array
{
    return [
        'company_id' => $responsible->company_id,
        'branch_id' => $responsible->branch_id,
        'department_id' => $responsible->department_id,
        'full_name' => $responsible->full_name,
        'position' => $responsible->position,
        'email' => $responsible->email,
        'phone' => $responsible->phone,
        'active' => $active,
    ];
}

test('deactivating a responsible returns all their assets to the company', function () {
    $assets = Asset::factory()->count(2)->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'current_responsible_id' => $this->responsible->id,
    ]);

    $this->actingAs($this->user)
        ->put("/responsables/{$this->responsible->id}", updateResponsiblePayload($this->responsible, false))
        ->assertRedirect();

    foreach ($assets as $asset) {
        expect($asset->fresh()->current_responsible_id)->toBeNull();

        $movement = $asset->fresh()->movements()->where('type', MovementType::CambioResponsable)->latest()->first();
        expect($movement)->not->toBeNull();
        expect($movement->comment)->toContain('Devuelto a la empresa')
            ->and($movement->comment)->toContain($this->user->name);
    }
});

test('deactivating a responsible with no assets does not fail and logs nothing', function () {
    $this->actingAs($this->user)
        ->put("/responsables/{$this->responsible->id}", updateResponsiblePayload($this->responsible, false))
        ->assertRedirect();

    expect($this->responsible->fresh()->active)->toBeFalse();
});

test('editing an already inactive responsible without changing active does not re-run the return process', function () {
    $this->responsible->update(['active' => false]);

    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'current_responsible_id' => null,
    ]);

    // Reasigna manualmente después de la baja (comportamiento normal del
    // botón "Asignar"): un update posterior del responsable con
    // active=false de nuevo no debe tocar este activo otra vez.
    $asset->update(['current_responsible_id' => $this->responsible->id]);

    $this->actingAs($this->user)
        ->put("/responsables/{$this->responsible->id}", updateResponsiblePayload($this->responsible, false))
        ->assertRedirect();

    expect($asset->fresh()->current_responsible_id)->toBe($this->responsible->id);
});

test('reactivating a responsible does not touch any asset', function () {
    $this->responsible->update(['active' => false]);

    $this->actingAs($this->user)
        ->put("/responsables/{$this->responsible->id}", updateResponsiblePayload($this->responsible, true))
        ->assertRedirect();

    expect($this->responsible->fresh()->active)->toBeTrue();
});
