<?php

use App\Models\Company;
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
