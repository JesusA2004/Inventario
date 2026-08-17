<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\AssetLabelPdfService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Equipo de cómputo', 'code' => 'EC', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);
});

test('the public QR page is accessible without authentication', function () {
    $this->get("/a/{$this->asset->public_id}")->assertOk();
});

test('the public QR url stays the same after editing the asset', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $originalPublicId = $this->asset->public_id;

    $this->actingAs($user)->put("/activos/{$this->asset->public_id}", [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Nombre actualizado',
        'internal_code' => $this->asset->internal_code,
        'status' => $this->asset->status->value,
        'acquired_at' => $this->asset->acquired_at->toDateString(),
    ]);

    $this->asset->refresh();
    expect($this->asset->public_id)->toBe($originalPublicId);
    $this->get("/a/{$originalPublicId}")->assertOk();
});

test('a logged in user can download the qr image and the label pdf', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->get("/activos/{$this->asset->public_id}/qr")->assertOk();
    $this->actingAs($user)->get("/activos/{$this->asset->public_id}/qr/descargar")->assertOk();
    $this->actingAs($user)->get("/activos/{$this->asset->public_id}/etiqueta")->assertOk();
});

test('the label center can generate a pdf for selected assets', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->get('/etiquetas')->assertOk();

    $this->actingAs($user)->post('/etiquetas/pdf', [
        'asset_ids' => [$this->asset->id],
    ])->assertOk();
});

test('a zip of qr codes can be generated for a selection of assets', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $response = $this->actingAs($user)->post('/activos-qr-zip', [
        'asset_ids' => [$this->asset->id],
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/zip');
});

test('the selected label size and custom dimensions are accepted and reach the pdf', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->post('/etiquetas/pdf', [
        'asset_ids' => [$this->asset->id],
        'template' => 'standard',
        'size' => 'large',
    ])->assertOk();

    $this->actingAs($user)->post('/etiquetas/pdf', [
        'asset_ids' => [$this->asset->id],
        'template' => 'compact',
        'size' => 'custom',
        'width_mm' => 45,
        'height_mm' => 35,
    ])->assertOk();
});

test('a label size that would not fit the chosen number of columns is rejected', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->post('/etiquetas/pdf', [
        'asset_ids' => [$this->asset->id],
        'template' => 'compact',
        'size' => 'large',
    ])->assertStatus(422);

    $this->actingAs($user)->post('/etiquetas/pdf', [
        'asset_ids' => [$this->asset->id],
        'template' => 'compact',
        'size' => 'custom',
        'width_mm' => 80,
        'height_mm' => 60,
    ])->assertStatus(422);
});

test('the single label pdf fits on a single page for every size, including tight custom bounds', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $service = app(AssetLabelPdfService::class);
    $asset = $this->asset->fresh(['assetType', 'company']);

    foreach (['small', 'medium', 'large'] as $size) {
        $pdf = $service->buildSingleLabel($asset, $size);
        $pdf->render();

        expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
    }

    // Casos límite: muy angosta y baja, muy ancha y baja, muy angosta y alta.
    foreach ([[25.0, 20.0], [90.0, 20.0], [25.0, 70.0], [90.0, 70.0]] as [$width, $height]) {
        $pdf = $service->buildSingleLabel($asset, 'custom', $width, $height);
        $pdf->render();

        expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
    }
});

test('the single label print accepts a size and reaches the pdf', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    $this->actingAs($user)->get("/activos/{$this->asset->public_id}/etiqueta?size=large")->assertOk();

    $this->actingAs($user)->get("/activos/{$this->asset->public_id}/etiqueta?size=custom&width_mm=60&height_mm=45")->assertOk();
});

test('the filtered ids endpoint returns every asset matching the current filters, not just one page', function () {
    $user = User::factory()->create();
    $user->assignRole('superadministrador');

    Asset::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $response = $this->actingAs($user)->get('/activos/ids-filtrados?company_id='.$this->company->id);

    $response->assertOk();
    expect($response->json('ids'))->toHaveCount(4);
});
