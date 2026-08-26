<?php

use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Storage::fake('public');

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('the four branding images can be uploaded independently for a company', function () {
    $this->actingAs($this->user)->patch("/configuracion/responsivas/{$this->company->id}", [
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'signing_link_valid_days' => 7,
        'header_logo' => UploadedFile::fake()->image('logo.png'),
        'watermark' => UploadedFile::fake()->image('watermark.png'),
        'footer' => UploadedFile::fake()->image('footer.png'),
    ])->assertRedirect();

    $settings = CompanyDocumentSetting::forCompany($this->company->fresh());

    expect($settings->header_logo_path)->not->toBeNull();
    expect($settings->watermark_path)->not->toBeNull();
    expect($settings->footer_path)->not->toBeNull();
    expect($settings->letterhead_path)->toBeNull();

    Storage::disk('public')->assertExists($settings->header_logo_path);
    Storage::disk('public')->assertExists($settings->watermark_path);
    Storage::disk('public')->assertExists($settings->footer_path);
});

test('replacing an image deletes the previous file instead of leaving it orphaned', function () {
    $settings = CompanyDocumentSetting::forCompany($this->company);
    $original = UploadedFile::fake()->image('old-logo.png')->store('branding/responsivas/'.$this->company->id, 'public');
    $settings->update(['header_logo_path' => $original]);

    $this->actingAs($this->user)->patch("/configuracion/responsivas/{$this->company->id}", [
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'signing_link_valid_days' => 7,
        'header_logo' => UploadedFile::fake()->image('new-logo.png'),
    ]);

    Storage::disk('public')->assertMissing($original);
    expect($settings->fresh()->header_logo_path)->not->toBe($original);
});

test('an image can be removed without uploading a replacement', function () {
    $settings = CompanyDocumentSetting::forCompany($this->company);
    $path = UploadedFile::fake()->image('watermark.png')->store('branding/responsivas/'.$this->company->id, 'public');
    $settings->update(['watermark_path' => $path]);

    $this->actingAs($this->user)->patch("/configuracion/responsivas/{$this->company->id}", [
        'default_grantor_name' => 'Jesus Arizmendi Maya',
        'signing_link_valid_days' => 7,
        'remove_watermark' => true,
    ]);

    Storage::disk('public')->assertMissing($path);
    expect($settings->fresh()->watermark_path)->toBeNull();
});

test('a new template version can be created with the exact-format fields', function () {
    $this->actingAs($this->user)->post('/configuracion/responsivas/plantillas', [
        'company_id' => $this->company->id,
        'name' => 'default',
        'title' => 'Responsiva de equipo — MR LANA',
        'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
        'recipient_block' => "A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.\nPRESENTE.",
        'body' => 'Clausulado de prueba.',
    ])->assertRedirect();

    $this->assertDatabaseHas('responsibility_templates', [
        'company_id' => $this->company->id,
        'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
    ]);
});
