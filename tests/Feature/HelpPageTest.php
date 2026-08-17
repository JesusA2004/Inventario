<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('the help page requires authentication', function () {
    $this->get('/ayuda')->assertRedirect();
});

test('any logged in user can open the help page', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('consulta');

    $this->actingAs($user)
        ->get('/ayuda')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('ayuda/Index'));
});
