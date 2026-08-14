<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;

test('the dashboard renders with data and respects company filters', function () {
    $user = User::factory()->create();

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    Asset::factory()->count(3)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'asset_type_id' => $assetType->id,
    ]);

    $response = $this->actingAs($user)->get('/dashboard?company_id='.$company->id.'&months=6');

    $response->assertOk();
});
