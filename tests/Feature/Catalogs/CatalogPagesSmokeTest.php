<?php

use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('catalog index pages render for a superadmin', function (string $url) {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->get($url)->assertOk();
})->with([
    '/empresas',
    '/sucursales',
    '/areas',
    '/marcas',
    '/tipos-activo',
    '/responsables',
]);

test('a company can be created, updated and deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->post('/empresas', [
        'name' => 'MR LANA',
        'code' => 'CML',
        'active' => true,
    ])->assertRedirect();

    $company = Company::query()->where('code', 'CML')->firstOrFail();

    $this->actingAs($user)->put("/empresas/{$company->id}", [
        'name' => 'MR LANA',
        'code' => 'CML',
        'active' => false,
    ])->assertRedirect();

    expect($company->fresh()->active)->toBeFalse();

    $this->actingAs($user)->delete("/empresas/{$company->id}")->assertRedirect();

    expect(Company::query()->find($company->id))->toBeNull();
});

test('a company with a related branch cannot be deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    Branch::create(['company_id' => $company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);

    $this->actingAs($user)->delete("/empresas/{$company->id}")->assertRedirect();

    expect(Company::query()->find($company->id))->not->toBeNull();
});

test('a branch can be created, updated and deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);

    $this->actingAs($user)->post('/sucursales', [
        'company_id' => $company->id,
        'name' => 'Corporativo',
        'code' => 'CORP',
        'active' => true,
    ])->assertRedirect();

    $branch = Branch::query()->where('code', 'CORP')->firstOrFail();

    $this->actingAs($user)->put("/sucursales/{$branch->id}", [
        'company_id' => $company->id,
        'name' => 'Corporativo Norte',
        'code' => 'CORP',
        'active' => true,
    ])->assertRedirect();

    expect($branch->fresh()->name)->toBe('Corporativo Norte');

    $this->actingAs($user)->delete("/sucursales/{$branch->id}")->assertRedirect();

    expect(Branch::query()->find($branch->id))->toBeNull();
});

test('a department can be created, updated and deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);

    $this->actingAs($user)->post('/areas', [
        'company_id' => $company->id,
        'name' => 'Sistemas',
        'code' => 'SIS',
        'active' => true,
    ])->assertRedirect();

    $department = Department::query()->where('code', 'SIS')->firstOrFail();

    $this->actingAs($user)->put("/areas/{$department->id}", [
        'company_id' => $company->id,
        'name' => 'Sistemas y Redes',
        'code' => 'SIS',
        'active' => true,
    ])->assertRedirect();

    expect($department->fresh()->name)->toBe('Sistemas y Redes');

    $this->actingAs($user)->delete("/areas/{$department->id}")->assertRedirect();

    expect(Department::query()->find($department->id))->toBeNull();
});

test('a brand can be created, updated and deleted, and duplicate names are rejected', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->post('/marcas', ['name' => 'Dell', 'active' => true])->assertRedirect();

    $brand = Brand::query()->where('name', 'Dell')->firstOrFail();

    $this->actingAs($user)->post('/marcas', ['name' => 'Dell', 'active' => true])
        ->assertSessionHasErrors('name');

    $this->actingAs($user)->put("/marcas/{$brand->id}", ['name' => 'Dell Technologies', 'active' => true])->assertRedirect();

    expect($brand->fresh()->name)->toBe('Dell Technologies');

    $this->actingAs($user)->delete("/marcas/{$brand->id}")->assertRedirect();

    expect(Brand::query()->find($brand->id))->toBeNull();
});

test('an asset type can be created, updated and deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->post('/tipos-activo', ['name' => 'Laptop', 'code' => 'LAP', 'active' => true])->assertRedirect();

    $assetType = AssetType::query()->where('code', 'LAP')->firstOrFail();

    $this->actingAs($user)->put("/tipos-activo/{$assetType->id}", ['name' => 'Laptop / Notebook', 'code' => 'LAP', 'active' => true])->assertRedirect();

    expect($assetType->fresh()->name)->toBe('Laptop / Notebook');

    $this->actingAs($user)->delete("/tipos-activo/{$assetType->id}")->assertRedirect();

    expect(AssetType::query()->find($assetType->id))->toBeNull();
});

test('a responsible person can be created, updated and deleted', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);

    $this->actingAs($user)->post('/responsables', [
        'company_id' => $company->id,
        'full_name' => 'Jesús Arizmendi',
        'active' => true,
    ])->assertRedirect();

    $responsible = ResponsiblePerson::query()->where('full_name', 'Jesús Arizmendi')->firstOrFail();

    $this->actingAs($user)->put("/responsables/{$responsible->id}", [
        'company_id' => $company->id,
        'full_name' => 'Jesús Arizmendi Maya',
        'active' => true,
    ])->assertRedirect();

    expect($responsible->fresh()->full_name)->toBe('Jesús Arizmendi Maya');

    $this->actingAs($user)->delete("/responsables/{$responsible->id}")->assertRedirect();

    expect(ResponsiblePerson::query()->find($responsible->id))->toBeNull();
});
