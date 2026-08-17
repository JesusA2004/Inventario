<?php

use App\Enums\LoanStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);
    $this->responsible = ResponsiblePerson::create(['company_id' => $this->company->id, 'full_name' => 'Jesús Arizmendi', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('a loan can be created for an asset and it appears on its ficha', function () {
    $this->actingAs($this->user)->post('/prestamos', [
        'asset_id' => $this->asset->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->toDateString(),
        'expected_return_date' => now()->addDays(5)->toDateString(),
        'origin' => 'index',
    ])->assertRedirect('/prestamos');

    $loan = $this->asset->loans()->first();
    expect($loan)->not->toBeNull();
    expect($loan->status)->toBe(LoanStatus::Prestado);
    expect($this->asset->movements()->where('type', MovementType::Prestamo)->exists())->toBeTrue();
});

test('the expected return date cannot be before the loan date, and the error is a plain Spanish sentence', function () {
    $response = $this->actingAs($this->user)->post('/prestamos', [
        'asset_id' => $this->asset->id,
        'loan_date' => now()->toDateString(),
        'expected_return_date' => now()->subDay()->toDateString(),
        'origin' => 'index',
    ]);

    $response->assertSessionHasErrors('expected_return_date');
    expect(session('errors')->get('expected_return_date'))->toBe([
        'La fecha de devolución esperada debe ser igual o posterior a la fecha de salida del préstamo.',
    ]);

    expect($this->asset->loans()->count())->toBe(0);
});

test('the actual return date cannot be before the loan date', function () {
    $loan = $this->asset->loans()->create([
        'company_id' => $this->company->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->subDays(2),
        'status' => LoanStatus::Prestado,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->post("/prestamos/{$loan->id}/devolver", [
        'actual_return_date' => now()->subDays(5)->toDateString(),
    ]);

    $response->assertSessionHasErrors('actual_return_date');
    expect(session('errors')->get('actual_return_date'))->toBe([
        'La fecha de devolución debe ser igual o posterior a la fecha de salida del préstamo.',
    ]);

    expect($loan->fresh()->status)->toBe(LoanStatus::Prestado);
});

test('a loan can be returned', function () {
    $loan = $this->asset->loans()->create([
        'company_id' => $this->company->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->subDays(2),
        'status' => LoanStatus::Prestado,
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)->post("/prestamos/{$loan->id}/devolver", [
        'actual_return_date' => now()->toDateString(),
    ])->assertRedirect();

    $loan->refresh();
    expect($loan->status)->toBe(LoanStatus::Devuelto);
    expect($this->asset->movements()->where('type', MovementType::Devolucion)->exists())->toBeTrue();
});

test('an asset cannot have two simultaneous active loans', function () {
    $this->asset->loans()->create([
        'company_id' => $this->company->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->subDay(),
        'status' => LoanStatus::Prestado,
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)->post('/prestamos', [
        'asset_id' => $this->asset->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->toDateString(),
        'origin' => 'index',
    ])->assertSessionHasErrors('asset_id');

    expect($this->asset->loans()->where('status', LoanStatus::Prestado)->count())->toBe(1);
});

test('a new loan can be created after the previous one on the same asset is returned', function () {
    $previous = $this->asset->loans()->create([
        'company_id' => $this->company->id,
        'loan_date' => now()->subDays(3),
        'status' => LoanStatus::Devuelto,
        'actual_return_date' => now()->subDay(),
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)->post('/prestamos', [
        'asset_id' => $this->asset->id,
        'assigned_to_responsible_id' => $this->responsible->id,
        'loan_date' => now()->toDateString(),
        'origin' => 'index',
    ])->assertRedirect('/prestamos');

    expect($this->asset->loans()->where('status', LoanStatus::Prestado)->count())->toBe(1);
    expect($previous->fresh()->status)->toBe(LoanStatus::Devuelto);
});

test('an overdue loan is reported with the vencido status', function () {
    $loan = $this->asset->loans()->create([
        'company_id' => $this->company->id,
        'loan_date' => now()->subDays(10),
        'expected_return_date' => now()->subDays(2),
        'status' => LoanStatus::Prestado,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get('/prestamos?status=vencido');
    $response->assertOk();

    expect($loan->isOverdue())->toBeTrue();
});
