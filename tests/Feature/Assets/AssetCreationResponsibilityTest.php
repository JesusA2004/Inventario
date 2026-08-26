<?php

use App\Enums\LegalReviewStatus;
use App\Models\Asset;
use App\Models\AssetResponsibility;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\PrivacyNoticeVersion;
use App\Models\ResponsibilityTemplate;
use App\Models\ResponsiblePerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Angélica Libreros Sandoval',
        'active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');

    $this->payload = [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Laptop de prueba',
        'internal_code' => 'CML-LAP-001',
        'status' => 'activo',
        'acquired_at' => now()->toDateString(),
    ];
});

test('an asset can be created without a responsible person', function () {
    $this->actingAs($this->user)->post('/activos', $this->payload)->assertRedirect();

    expect(AssetResponsibility::count())->toBe(0);
});

test('assigning a real responsible during creation generates a draft responsibility when documents are configured', function () {
    ResponsibilityTemplate::create([
        'company_id' => null,
        'name' => 'default',
        'title' => 'Clausulado de prueba',
        'version' => 1,
        'body' => 'Texto legal de prueba.',
        'active' => true,
        'legal_review_status' => LegalReviewStatus::Approved,
    ]);
    PrivacyNoticeVersion::create(['version' => 1, 'body' => 'Aviso de privacidad de prueba.', 'active' => true]);
    CompanyDocumentSetting::forCompany($this->company)->update([
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'default_grantor_position' => 'Encargado de Sistemas',
    ]);

    $this->actingAs($this->user)->post('/activos', [
        ...$this->payload,
        'current_responsible_id' => $this->responsible->id,
    ])->assertRedirect();

    $responsibility = AssetResponsibility::first();

    expect($responsibility)->not->toBeNull();
    expect($responsibility->receiver_name)->toBe('Angélica Libreros Sandoval');
    expect($responsibility->status->value)->toBe('borrador');
});

test('turning off the toggle during creation skips generating the responsibility', function () {
    ResponsibilityTemplate::create([
        'company_id' => null,
        'name' => 'default',
        'title' => 'Clausulado de prueba',
        'version' => 1,
        'body' => 'Texto legal de prueba.',
        'active' => true,
        'legal_review_status' => LegalReviewStatus::Approved,
    ]);
    PrivacyNoticeVersion::create(['version' => 1, 'body' => 'Aviso de privacidad de prueba.', 'active' => true]);
    CompanyDocumentSetting::forCompany($this->company)->update(['default_grantor_name' => 'Jesus Arizmendi Maya']);

    $this->actingAs($this->user)->post('/activos', [
        ...$this->payload,
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => false,
    ])->assertRedirect();

    expect(AssetResponsibility::count())->toBe(0);
});

test('assigning a responsible without a legal template configured still creates the asset', function () {
    $response = $this->actingAs($this->user)->post('/activos', [
        ...$this->payload,
        'current_responsible_id' => $this->responsible->id,
    ]);

    $response->assertRedirect();
    expect(AssetResponsibility::count())->toBe(0);
    expect(Asset::where('internal_code', 'CML-LAP-001')->first())->not->toBeNull();
});
