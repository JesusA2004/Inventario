<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->seed(RolesAndPermissionsSeeder::class);

    $this->company = Company::create(['name' => 'MR LANA', 'code' => 'CML', 'active' => true]);
    $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Corporativo', 'code' => 'CORP', 'active' => true]);
    $this->assetType = AssetType::create(['name' => 'Laptop', 'code' => 'LAP', 'active' => true]);

    $this->asset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('superadministrador');
});

test('an uploaded invoice is stored on the private disk, not the public one', function () {
    $invoice = UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf');

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/archivos", [
        'type' => 'factura',
        'file' => $invoice,
    ])->assertRedirect();

    $file = $this->asset->files()->where('type', 'factura')->firstOrFail();

    expect($file->disk)->toBe('local');
    Storage::disk('local')->assertExists($file->path);
    Storage::disk('public')->assertMissing($file->path);
});

test('an uploaded photo stays on the public disk for thumbnails', function () {
    $photo = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/archivos", [
        'type' => 'foto',
        'file' => $photo,
    ])->assertRedirect();

    $file = $this->asset->files()->where('type', 'foto')->firstOrFail();

    expect($file->disk)->toBe('public');
    Storage::disk('public')->assertExists($file->path);
});

test('an unauthenticated request cannot download a private invoice', function () {
    $file = $this->asset->files()->create([
        'type' => 'factura',
        'disk' => 'local',
        'path' => 'assets/'.$this->asset->id.'/factura/test.pdf',
        'original_name' => 'factura.pdf',
        'mime' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $this->user->id,
    ]);

    $this->get("/activos/{$this->asset->public_id}/archivos/{$file->id}/descargar")->assertRedirect();
});

test('an authorized user can download a private invoice', function () {
    Storage::disk('local')->put('assets/'.$this->asset->id.'/factura/test.pdf', 'contenido-de-prueba');

    $file = $this->asset->files()->create([
        'type' => 'factura',
        'disk' => 'local',
        'path' => 'assets/'.$this->asset->id.'/factura/test.pdf',
        'original_name' => 'factura.pdf',
        'mime' => 'application/pdf',
        'size' => 20,
        'uploaded_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get("/activos/{$this->asset->public_id}/archivos/{$file->id}/descargar");

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('factura.pdf');
});

test('the generated file url uses the asset public_id, not its numeric id, so it actually resolves', function () {
    Storage::disk('local')->put('assets/'.$this->asset->id.'/factura/test.pdf', 'contenido-de-prueba');

    $file = $this->asset->files()->create([
        'type' => 'factura',
        'disk' => 'local',
        'path' => 'assets/'.$this->asset->id.'/factura/test.pdf',
        'original_name' => 'factura.pdf',
        'mime' => 'application/pdf',
        'size' => 20,
        'uploaded_by' => $this->user->id,
    ]);

    $file->load('asset');

    expect($file->url)->toBe(route('assets.files.download', ['asset' => $this->asset->public_id, 'file' => $file->id]));

    $this->actingAs($this->user)->get($file->url)->assertOk();
});

test('a file that does not belong to the requested asset cannot be downloaded through it', function () {
    $otherAsset = Asset::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
    ]);

    Storage::disk('local')->put('assets/'.$otherAsset->id.'/factura/test.pdf', 'contenido-de-prueba');

    $file = $otherAsset->files()->create([
        'type' => 'factura',
        'disk' => 'local',
        'path' => 'assets/'.$otherAsset->id.'/factura/test.pdf',
        'original_name' => 'factura.pdf',
        'mime' => 'application/pdf',
        'size' => 20,
        'uploaded_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get("/activos/{$this->asset->public_id}/archivos/{$file->id}/descargar")
        ->assertNotFound();
});

test('the private disk is no longer exposed through the direct storage url', function () {
    Storage::disk('local')->put('assets/leak-test.pdf', 'contenido-de-prueba');

    $this->actingAs($this->user)->get('/storage/assets/leak-test.pdf')->assertNotFound();
});

test('the asset resource never exposes the physical disk or path of a file', function () {
    $file = $this->asset->files()->create([
        'type' => 'factura',
        'disk' => 'local',
        'path' => 'assets/'.$this->asset->id.'/factura/test.pdf',
        'original_name' => 'factura.pdf',
        'mime' => 'application/pdf',
        'size' => 20,
        'uploaded_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get("/activos/{$this->asset->public_id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('asset.files.0', fn ($assertable) => $assertable
            ->where('id', $file->id)
            ->missing('path')
            ->missing('disk')
            ->has('url')
            ->etc()
        )
    );
});
