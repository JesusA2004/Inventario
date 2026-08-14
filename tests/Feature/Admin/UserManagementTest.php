<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('superadministrador');
});

test('an admin can create a user and assign a role', function () {
    $this->actingAs($this->admin)->post('/usuarios', [
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@example.com',
        'role' => 'sistemas',
    ])->assertRedirect();

    $user = User::query()->where('email', 'nuevo@example.com')->firstOrFail();
    expect($user->hasRole('sistemas'))->toBeTrue();
    expect($user->is_active)->toBeTrue();
});

test('an admin can deactivate a user and it blocks login', function () {
    $user = User::factory()->create();
    $user->assignRole('consulta');

    $this->actingAs($this->admin)->post("/usuarios/{$user->id}/estado")->assertRedirect();
    $user->refresh();
    expect($user->is_active)->toBeFalse();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
});

test('a non-admin cannot access user management', function () {
    $user = User::factory()->create();
    $user->assignRole('consulta');

    $this->actingAs($user)->get('/usuarios')->assertForbidden();
});
