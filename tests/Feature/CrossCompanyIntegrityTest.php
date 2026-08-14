<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->mrLana = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->mrInsight = Company::create(['name' => 'MR INSIGHT', 'code' => 'MRI', 'active' => true]);

    $this->mrLanaBranch = Branch::create(['company_id' => $this->mrLana->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->mrInsightBranch = Branch::create(['company_id' => $this->mrInsight->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);

    $this->mrInsightResponsible = ResponsiblePerson::create([
        'company_id' => $this->mrInsight->id,
        'branch_id' => $this->mrInsightBranch->id,
        'full_name' => 'Responsable MRI',
        'active' => true,
    ]);

    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('an asset cannot mix a company with a branch from another company', function () {
    $response = $this->actingAs($this->user)->post('/activos', [
        'company_id' => $this->mrLana->id,
        'branch_id' => $this->mrInsightBranch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Laptop de prueba',
        'internal_code' => 'CML-LAP-999',
        'status' => 'activo',
        'acquired_at' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('branch_id');
    expect(Asset::where('internal_code', 'CML-LAP-999')->exists())->toBeFalse();
});

test('an asset cannot mix a company with a responsible person from another company', function () {
    $response = $this->actingAs($this->user)->post('/activos', [
        'company_id' => $this->mrLana->id,
        'branch_id' => $this->mrLanaBranch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Laptop de prueba',
        'internal_code' => 'CML-LAP-998',
        'status' => 'activo',
        'acquired_at' => now()->toDateString(),
        'current_responsible_id' => $this->mrInsightResponsible->id,
    ]);

    $response->assertSessionHasErrors('current_responsible_id');
    expect(Asset::where('internal_code', 'CML-LAP-998')->exists())->toBeFalse();
});

test('an audit cannot mix a company with a branch from another company', function () {
    $response = $this->actingAs($this->user)->post('/auditorias', [
        'company_id' => $this->mrLana->id,
        'branch_id' => $this->mrInsightBranch->id,
    ]);

    $response->assertSessionHasErrors('branch_id');
});
