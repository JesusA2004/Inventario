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
use App\Services\Responsibilities\AssetResponsibilityPdfService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'model' => 'EliteBook 845 G8',
    ]);

    $this->responsible = ResponsiblePerson::create([
        'company_id' => $this->company->id,
        'full_name' => 'Angélica Libreros Sandoval',
        'position' => 'Coordinador Regional',
        'active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');

    ResponsibilityTemplate::create([
        'company_id' => $this->company->id,
        'name' => 'default',
        'title' => 'Responsiva de equipo de cómputo — MR LANA',
        'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
        'recipient_block' => "A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.\nPRESENTE.",
        'version' => 1,
        'body' => "Yo, {{ receiver_name }}, en mi calidad de receptor.\n\n{{ characteristics_table }}\n\nQue el patrón, de conformidad con lo establecido por el artículo 32, fracción III de la Ley Federal del Trabajo, entrega el equipo.",
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

test('the document title comes from the template, never from a generic fallback', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ])->assertRedirect();

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    expect($responsibility->asset_snapshot['document_title'])->toBe('RESPONSIVA DE EQUIPO DE CÓMPUTO');
});

test('the recipient block placeholders resolve, but the reserved characteristics token is left intact for the PDF renderer', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    expect($responsibility->asset_snapshot['recipient_block'])->toBe(
        "A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.\nPRESENTE.",
    );
    expect($responsibility->legal_text_snapshot)->toContain('Yo, Angélica Libreros Sandoval, en mi calidad de receptor.');
    expect($responsibility->legal_text_snapshot)->toContain(ResponsibilityTemplate::CHARACTERISTICS_TABLE_TOKEN);
    expect($responsibility->legal_text_snapshot)->toContain('artículo 32, fracción III');
});

test('the resulting pdf is a single page with the letter format and a correct page count', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $pdfService = app(AssetResponsibilityPdfService::class);
    $pdfService->unsigned($responsibility);

    $rendered = Storage::disk('local')->get($responsibility->fresh()->unsigned_pdf_path);

    expect($rendered)->toStartWith('%PDF');
});

test('a page-break token in the template forces exactly two pages', function () {
    $this->responsible->update(['position' => 'Coordinador Regional']);

    ResponsibilityTemplate::where('company_id', $this->company->id)->update([
        'body' => "Yo, {{ receiver_name }}, en mi calidad de receptor.\n\n{{ characteristics_table }}\n\n".
            "Que está estrictamente prohibido al suscrito modificar el equipo.\n\n".
            '{{ page_break }}'."\n\n".
            'Que el patrón cubrirá los servicios del equipo de cómputo.',
    ]);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();
    expect($responsibility->legal_text_snapshot)->toContain(ResponsibilityTemplate::PAGE_BREAK_TOKEN);

    $pdfService = app(AssetResponsibilityPdfService::class);
    $refl = new ReflectionClass($pdfService);
    $method = $refl->getMethod('render');
    $method->setAccessible(true);
    $pdf = $method->invoke($pdfService, $responsibility);
    $pdf->render();

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(2);
});

test('the signature block shows the real position of the receiver, and never prints a specific grantor name or position', function () {
    $this->responsible->update(['position' => 'Coordinador Regional']);

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $response = $this->actingAs($this->user)->get("/responsivas/{$responsibility->public_id}/vista-previa");

    $response->assertOk();
    $response->assertSee('RECEPTOR', false);
    $response->assertSee('OTORGANTE', false);
    // Receptor sí lleva nombre y puesto real impresos.
    $response->assertSee('Angélica Libreros Sandoval', false);
    $response->assertSee('Coordinador Regional', false);
    // Otorgante deliberadamente NO lleva nombre ni puesto impreso: lo puede
    // firmar cualquier apoderado legal de la empresa, no una persona fija.
    $response->assertDontSee('Encargado de Sistemas', false);
    $response->assertDontSee($responsibility->grantor_name, false);
});

test('the on-screen preview renders the real recipient text and never the removed draft banner', function () {
    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/cambiar-responsable", [
        'current_responsible_id' => $this->responsible->id,
        'generate_responsibility' => true,
    ]);

    $responsibility = AssetResponsibility::where('asset_id', $this->asset->id)->firstOrFail();

    $response = $this->actingAs($this->user)->get("/responsivas/{$responsibility->public_id}/vista-previa");

    $response->assertOk();
    $response->assertSee('RESPONSIVA DE EQUIPO DE CÓMPUTO');
    $response->assertSee('PRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.', false);
    $response->assertDontSee('BORRADOR');
    $response->assertDontSee('DESTINATARIO / EMPRESA', false);
});
