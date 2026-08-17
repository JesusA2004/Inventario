<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Part;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('an asset with assembled parts exposes everything the piezas tab needs', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);
    $brand = Brand::create(['name' => 'Kingston']);

    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'asset_type_id' => $assetType->id,
    ]);

    $part = Part::factory()->create([
        'company_id' => $company->id,
        'brand_id' => $brand->id,
        'related_asset_id' => $asset->id,
        'assembled' => true,
        'part_number' => 'KVR16N11-8',
        'serial_number' => 'SN-12345',
        'quantity' => 2,
    ]);

    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $response = $this->actingAs($user)->get("/activos/{$asset->public_id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('asset.parts.0', fn ($assertable) => $assertable
            ->where('id', $part->id)
            ->where('brand', 'Kingston')
            ->where('part_number', 'KVR16N11-8')
            ->where('serial_number', 'SN-12345')
            ->where('assembled', true)
            ->where('quantity', 2)
            ->etc()
        )
    );
});
