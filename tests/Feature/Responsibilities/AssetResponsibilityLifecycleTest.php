<?php

use App\Enums\AssetResponsibilityStatus;
use App\Enums\LegalReviewStatus;
use App\Enums\MovementType;
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
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true, 'responsibility_profile' => 'computer']);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'model' => 'EliteBook 845 G8',
        'serial_number' => 'CML-0001',
    ]);

    $this->responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Angélica Libreros Sandoval',
        'position' => 'Analista',
        'email' => 'angelica@example.com',
        'active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');

    ResponsibilityTemplate::create([
        'company_id' => null,
        'name' => 'default',
        'title' => 'Clausulado de prueba',
        'version' => 1,
        'body' => 'Texto legal de prueba para el suite de tests.',
        'active' => true,
        'legal_review_status' => LegalReviewStatus::Approved,
    ]);

    PrivacyNoticeVersion::create(['version' => 1, 'body' => 'Aviso de privacidad de prueba.', 'active' => true]);

    CompanyDocumentSetting::forCompany($this->company)->update([
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'default_grantor_position' => 'Encargado de Sistemas',
        'issuing_city' => 'Cuernavaca, Morelos',
    ]);
});

test('assigning a real responsible with the toggle on generates a draft responsibility', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ])->assertRedirect();

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->first();

    expect($responsibility)->not->toBeNull();
    expect($responsibility->status->value)->toBe('borrador');
    expect($responsibility->receiver_name)->toBe('Angélica Libreros Sandoval');
    expect($responsibility->grantor_name)->toBe('Jesus Arizmendi Maya');
    expect($this->asset->movements()->where('type', MovementType::Responsiva)->exists())->toBeTrue();
});

test('the toggle off does not generate a responsibility', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => false,
    ])->assertRedirect();

    expect(AssetResponsibility::where('asset_id', $this->asset->id)->exists())->toBeFalse();
});

test('reassigning the responsible 3 times keeps 3 historical responsibilities', function () {
    $second = ResponsiblePerson::create(['company_id' => $this->company->id, 'full_name' => 'Segundo Responsable', 'active' => true]);
    $third = ResponsiblePerson::create(['company_id' => $this->company->id, 'full_name' => 'Tercer Responsable', 'active' => true]);

    foreach ([$this->responsible, $second, $third] as $responsible) {
        $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
            'current_responsible_id' => $responsible->id,
            'generate_responsibility' => true,
        ])->assertRedirect();
    }

    expect(AssetResponsibility::where('asset_id', $this->asset->id)->count())->toBe(3);
});

test('the responsibility snapshot does not change after editing the asset', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();
    $originalModel = $responsibility->asset_snapshot['asset']['model'];

    $this->asset->update(['model' => 'Modelo completamente distinto']);

    expect($responsibility->fresh()->asset_snapshot['asset']['model'])->toBe($originalModel);
    expect($responsibility->fresh()->asset_snapshot['asset']['model'])->not->toBe('Modelo completamente distinto');
});

test('the responsibility snapshot does not change after editing the responsible person', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $this->responsible->update(['full_name' => 'Nombre Cambiado', 'position' => 'Puesto nuevo']);

    expect($responsibility->fresh()->receiver_name)->toBe('Angélica Libreros Sandoval');
    expect($responsibility->fresh()->receiver_position)->toBe('Analista');
});

test('the responsibility snapshot does not change after changing the grantor configuration', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    CompanyDocumentSetting::forCompany($this->company)->update(['default_grantor_name' => 'Otra Persona']);

    expect($responsibility->fresh()->grantor_name)->toBe('Jesus Arizmendi Maya');
});

test('a responsibility without email still allows downloading the unsigned pdf', function () {
    $this->responsible->update(['email' => null]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $this->actingAs($this->user)->get("/responsivas/{$responsibility->public_id}/pdf")
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('a responsibility without email cannot be sent for digital signature', function () {
    $this->responsible->update(['email' => null]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $this->actingAs($this->user)->post("/responsivas/{$responsibility->public_id}/enviar")
        ->assertSessionHasErrors('receiver_email');

    expect($responsibility->fresh()->status->value)->toBe('borrador');
});

test('sending requires an approved legal template', function () {
    ResponsibilityTemplate::query()->update(['legal_review_status' => LegalReviewStatus::Draft]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $this->actingAs($this->user)->post("/responsivas/{$responsibility->public_id}/enviar")
        ->assertSessionHasErrors('status');
});

test('a signed responsibility can no longer modify its characteristics', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();
    $responsibility->update(['status' => AssetResponsibilityStatus::FirmadaDigitalmente]);

    $this->actingAs($this->user)->patch("/responsivas/{$responsibility->public_id}/caracteristicas", [
        'characteristics' => [['name' => 'Nuevo', 'value' => 'Valor']],
    ])->assertSessionHasErrors('status');
});

test('a cancelled responsibility cannot be sent for signature', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();
    $this->actingAs($this->user)->post("/responsivas/{$responsibility->public_id}/cancelar")->assertRedirect();

    expect($responsibility->fresh()->status->value)->toBe('cancelada');

    $this->actingAs($this->user)->post("/responsivas/{$responsibility->public_id}/enviar")
        ->assertSessionHasErrors('status');
});

test('generating a responsibility with no grantor configured falls back to the current user', function () {
    CompanyDocumentSetting::forCompany($this->company)->update([
        'default_grantor_name' => null,
        'default_grantor_position' => null,
    ]);

    $this->asset->update(['current_responsible_id' => $this->responsible->id]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/responsivas")
        ->assertRedirect();

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    expect($responsibility->grantor_name)->toBe($this->user->name);
    expect($responsibility->grantor_email)->toBe($this->user->email);
});

test('generating another responsibility for the same responsible records the reason in the asset history', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ])->assertRedirect();

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/responsivas", [
        'reason' => 'Se corrigió el otorgante en el documento anterior.',
    ])->assertRedirect();

    expect(AssetResponsibility::where('asset_id', $this->asset->id)->count())->toBe(2);

    $movement = $this->asset->movements()->where('type', MovementType::Responsiva)->latest()->first();
    expect($movement->comment)->toContain('Se corrigió el otorgante en el documento anterior.');
    expect($movement->user_id)->toBe($this->user->id);
});

test('a user without ver-evidencia-responsivas does not see the evidence block', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $auditor = User::factory()->create();
    $auditor->assignRole('auditor');

    $response = $this->actingAs($auditor)->get("/responsivas/{$responsibility->public_id}");
    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->missing('responsibility.evidence'));
});
