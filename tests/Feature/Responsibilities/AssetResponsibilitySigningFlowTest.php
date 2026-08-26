<?php

use App\Enums\AssetResponsibilityStatus;
use App\Enums\LegalReviewStatus;
use App\Mail\ResponsibilityInvitationMail;
use App\Mail\ResponsibilitySignedMail;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\PrivacyNoticeVersion;
use App\Models\ResponsibilityTemplate;
use App\Models\ResponsiblePerson;
use App\Models\User;
use App\Services\Responsibilities\AssetResponsibilityService;
use App\Services\Responsibilities\ResponsibilitySigningTokenService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true, 'responsibility_profile' => 'computer']);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Angélica Libreros Sandoval',
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
        'body' => 'Texto legal de prueba.',
        'active' => true,
        'legal_review_status' => LegalReviewStatus::Approved,
    ]);

    PrivacyNoticeVersion::create(['version' => 1, 'body' => 'Aviso de prueba.', 'active' => true]);

    CompanyDocumentSetting::forCompany($this->company)->update([
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'default_grantor_position' => 'Encargado de Sistemas',
        'issuing_city' => 'Cuernavaca, Morelos',
    ]);

    Mail::fake();

    $this->responsibility = app(AssetResponsibilityService::class)->createDraft($this->asset, $this->responsible, [], $this->user);

    // No es un PNG real, pero el endpoint público solo valida el prefijo
    // "data:image/png;base64," y un umbral de tamaño (ver
    // PublicResponsibilitySigningController::decodeSignaturePng()), igual
    // que lo haría un trazo real exportado por el canvas.
    $this->validSignature = 'data:image/png;base64,'.base64_encode(str_repeat('a', 600));
    $this->tinySignature = 'data:image/png;base64,'.base64_encode('x');
});

function extractToken(string $signingUrl): string
{
    parse_str((string) parse_url($signingUrl, PHP_URL_QUERY), $query);

    return $query['token'];
}

test('sending queues an invitation email to the correct responsible person', function () {
    $this->actingAs($this->user)->post("/responsivas/{$this->responsibility->public_id}/enviar")->assertRedirect();

    Mail::assertQueued(ResponsibilityInvitationMail::class, fn ($mail) => $mail->hasTo($this->responsible->email));
});

test('a valid token opens the signing page', function () {
    $this->actingAs($this->user)->post("/responsivas/{$this->responsibility->public_id}/enviar");

    $token = null;
    Mail::assertQueued(ResponsibilityInvitationMail::class, function ($mail) use (&$token) {
        $token = extractToken($mail->signingUrl);

        return true;
    });

    $this->get("/firmar/responsiva/{$this->responsibility->public_id}?token={$token}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('state', 'ok'));
});

test('an invalid token is rejected', function () {
    $this->get("/firmar/responsiva/{$this->responsibility->public_id}?token=un-token-invalido")->assertNotFound();
});

test('an expired token shows the expired page instead of a hard error', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);
    $this->responsibility->update(['signing_token_expires_at' => now()->subDay()]);

    $this->get("/firmar/responsiva/{$this->responsibility->public_id}?token={$token}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('state', 'expired'));
});

test('resending invalidates the previous signing token', function () {
    $this->actingAs($this->user)->post("/responsivas/{$this->responsibility->public_id}/enviar");

    $firstToken = null;
    Mail::assertQueued(ResponsibilityInvitationMail::class, function ($mail) use (&$firstToken) {
        $firstToken = extractToken($mail->signingUrl);

        return true;
    });

    $this->actingAs($this->user)->post("/responsivas/{$this->responsibility->public_id}/reenviar")->assertRedirect();

    $this->get("/firmar/responsiva/{$this->responsibility->public_id}?token={$firstToken}")->assertNotFound();
});

test('a cancelled responsibility cannot be signed', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);
    $this->responsibility->update(['status' => AssetResponsibilityStatus::Cancelada]);

    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'consent_accepted' => true,
        'privacy_accepted' => true,
        'signature' => $this->validSignature,
    ])->assertNotFound();
});

test('signing requires accepting the responsibility disclosure', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);

    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'privacy_accepted' => true,
        'signature' => $this->validSignature,
    ])->assertSessionHasErrors('consent_accepted');
});

test('signing requires accepting the privacy notice', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);

    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'consent_accepted' => true,
        'signature' => $this->validSignature,
    ])->assertSessionHasErrors('privacy_accepted');
});

test('an empty signature is rejected', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);

    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'consent_accepted' => true,
        'privacy_accepted' => true,
        'signature' => $this->tinySignature,
    ])->assertSessionHasErrors('signature');
});

test('signing stores a private signature file, hashes it, freezes a signed pdf, and emails the signed copy', function () {
    $token = app(ResponsibilitySigningTokenService::class)->issue($this->responsibility, 7);

    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'consent_accepted' => true,
        'privacy_accepted' => true,
        'signature' => $this->validSignature,
    ])->assertRedirect();

    $this->responsibility->refresh();

    expect($this->responsibility->status)->toBe(AssetResponsibilityStatus::FirmadaDigitalmente);
    expect($this->responsibility->signature_path)->not->toBeNull();
    expect($this->responsibility->signature_sha256)->not->toBeNull();
    expect($this->responsibility->signed_pdf_path)->not->toBeNull();
    expect($this->responsibility->document_sha256)->not->toBeNull();
    expect($this->responsibility->consent_accepted_at)->not->toBeNull();
    expect($this->responsibility->privacy_accepted_at)->not->toBeNull();

    Storage::disk('local')->assertExists($this->responsibility->signature_path);
    Storage::disk('local')->assertExists($this->responsibility->signed_pdf_path);

    Mail::assertQueued(ResponsibilitySignedMail::class, fn ($mail) => $mail->hasTo($this->responsible->email));

    // Ya no puede volver a firmarse con el mismo token (además, invalidate() lo borró).
    $this->post("/firmar/responsiva/{$this->responsibility->public_id}", [
        'token' => $token,
        'consent_accepted' => true,
        'privacy_accepted' => true,
        'signature' => $this->validSignature,
    ])->assertNotFound();
});

test('uploading a physical copy stores it privately and marks the responsibility accordingly', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('firmada.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)->post("/responsivas/{$this->responsibility->public_id}/copia-fisica", [
        'file' => $file,
    ])->assertRedirect();

    $this->responsibility->refresh();

    expect($this->responsibility->physical_copy_path)->not->toBeNull();
    expect($this->responsibility->status)->toBe(AssetResponsibilityStatus::FirmaFisicaCargada);
    Storage::disk('local')->assertExists($this->responsibility->physical_copy_path);
});

test('downloading a signed pdf for a responsibility that has none returns 404', function () {
    $this->actingAs($this->user)->get("/responsivas/{$this->responsibility->public_id}/pdf-firmado")->assertNotFound();
});

test('downloading a physical copy for a responsibility that has none returns 404', function () {
    $this->actingAs($this->user)->get("/responsivas/{$this->responsibility->public_id}/copia-fisica")->assertNotFound();
});
