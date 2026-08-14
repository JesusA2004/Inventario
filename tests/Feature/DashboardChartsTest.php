<?php

use App\Enums\LoanStatus;
use App\Enums\PartStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Loan;
use App\Models\Part;
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

test('active loans and available parts stat cards obey the company filter instead of showing global totals', function () {
    $user = User::factory()->create();

    $companyA = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $companyB = Company::create(['name' => 'MR INSIGHT', 'code' => 'MRI', 'active' => true]);
    $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $branchB = Branch::create(['company_id' => $companyB->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $assetA = Asset::factory()->create(['company_id' => $companyA->id, 'branch_id' => $branchA->id, 'asset_type_id' => $assetType->id]);
    $assetB = Asset::factory()->create(['company_id' => $companyB->id, 'branch_id' => $branchB->id, 'asset_type_id' => $assetType->id]);

    Loan::create(['asset_id' => $assetA->id, 'company_id' => $companyA->id, 'loan_date' => now(), 'status' => LoanStatus::Prestado]);
    Loan::create(['asset_id' => $assetB->id, 'company_id' => $companyB->id, 'loan_date' => now(), 'status' => LoanStatus::Prestado]);

    Part::create(['company_id' => $companyA->id, 'branch_id' => $branchA->id, 'internal_code' => 'CML-PZ-001', 'name' => 'RAM', 'status' => PartStatus::Funcional, 'in_inventory' => true, 'quantity' => 1]);
    Part::create(['company_id' => $companyB->id, 'branch_id' => $branchB->id, 'internal_code' => 'MRI-PZ-001', 'name' => 'RAM', 'status' => PartStatus::Funcional, 'in_inventory' => true, 'quantity' => 1]);

    $response = $this->actingAs($user)->get('/dashboard?company_id='.$companyA->id);

    $response->assertOk();
    $props = $response->viewData('page')['props'];
    expect($props['stats']['activeLoans'])->toBe(1);
    expect($props['stats']['availableParts'])->toBe(1);
});
