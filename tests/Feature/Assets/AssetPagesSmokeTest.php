<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('assets index, create and show pages render for a superadmin', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'asset_type_id' => $assetType->id,
    ]);

    $this->actingAs($user)->get('/activos')->assertOk();
    $this->actingAs($user)->get('/activos/crear')->assertOk();
    $this->actingAs($user)->get("/activos/{$asset->public_id}")->assertOk();
    $this->actingAs($user)->get("/activos/{$asset->public_id}/editar")->assertOk();
});
