<?php

use App\Enums\PartStatus;
use App\Models\Company;
use App\Models\Part;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('parts index, create and edit pages render', function () {
    $part = Part::factory()->create(['company_id' => $this->company->id]);

    $this->actingAs($this->user)->get('/piezas')->assertOk();
    $this->actingAs($this->user)->get('/piezas/crear')->assertOk();
    $this->actingAs($this->user)->get("/piezas/{$part->public_id}/editar")->assertOk();
});

test('a part can be created, updated and decommissioned without being deleted', function () {
    $this->actingAs($this->user)->post('/piezas', [
        'company_id' => $this->company->id,
        'internal_code' => 'CML-PZ-001',
        'name' => 'Memoria RAM 8GB',
        'status' => PartStatus::Funcional->value,
        'in_inventory' => true,
        'quantity' => 2,
    ])->assertRedirect('/piezas');

    $part = Part::query()->where('internal_code', 'CML-PZ-001')->firstOrFail();

    $this->actingAs($this->user)->put("/piezas/{$part->public_id}", [
        'company_id' => $this->company->id,
        'internal_code' => 'CML-PZ-001',
        'name' => 'Memoria RAM 16GB',
        'status' => PartStatus::Funcional->value,
        'in_inventory' => true,
        'quantity' => 2,
    ])->assertRedirect('/piezas');

    expect($part->fresh()->name)->toBe('Memoria RAM 16GB');

    $this->actingAs($this->user)->post("/piezas/{$part->public_id}/baja", [
        'reason' => 'Ya no sirve',
    ])->assertRedirect();

    $part->refresh();
    expect($part->in_inventory)->toBeFalse();
    expect($part->status)->toBe(PartStatus::Baja);
    expect(Part::query()->find($part->id))->not->toBeNull();
});
