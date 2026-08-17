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

test('a photo can be uploaded directly from the asset ficha', function () {
    $photo = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/archivos", [
        'type' => 'foto',
        'file' => $photo,
    ])->assertRedirect();

    expect($this->asset->files()->where('type', 'foto')->exists())->toBeTrue();
});

test('an invoice can be uploaded directly from the asset ficha', function () {
    $invoice = UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf');

    $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/archivos", [
        'type' => 'factura',
        'file' => $invoice,
    ])->assertRedirect();

    expect($this->asset->files()->where('type', 'factura')->exists())->toBeTrue();
});

test('a dangerous file is rejected even when disguised as an invoice', function () {
    $malicious = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

    $response = $this->actingAs($this->user)->post("/activos/{$this->asset->public_id}/archivos", [
        'type' => 'factura',
        'file' => $malicious,
    ]);

    $response->assertSessionHasErrors('file');
    expect($this->asset->files()->count())->toBe(0);
});

test('an uploaded file can be deleted from the ficha', function () {
    $file = $this->asset->files()->create([
        'type' => 'foto',
        'path' => 'assets/1/foto/test.jpg',
        'original_name' => 'test.jpg',
        'mime' => 'image/jpeg',
        'size' => 100,
        'uploaded_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete("/activos/{$this->asset->public_id}/archivos/{$file->id}")
        ->assertRedirect();

    expect($this->asset->files()->count())->toBe(0);
});

test('an invoice uploaded while creating an asset via AssetController::store is stored privately', function () {
    $invoice = UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf');

    $this->actingAs($this->user)->post('/activos', [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Laptop con factura',
        'internal_code' => 'CML-LAP-777',
        'status' => 'activo',
        'acquired_at' => now()->toDateString(),
        'invoice_file' => $invoice,
    ])->assertRedirect();

    $asset = Asset::query()->where('internal_code', 'CML-LAP-777')->firstOrFail();
    $file = $asset->files()->where('type', 'factura')->firstOrFail();

    expect($file->disk)->toBe('local');
    Storage::disk('local')->assertExists($file->path);
    Storage::disk('public')->assertMissing($file->path);
});

test('a photo uploaded while creating an asset via AssetController::store stays public', function () {
    $photo = UploadedFile::fake()->image('foto.jpg');

    $this->actingAs($this->user)->post('/activos', [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => 'Laptop con foto',
        'internal_code' => 'CML-LAP-778',
        'status' => 'activo',
        'acquired_at' => now()->toDateString(),
        'photos' => [$photo],
    ])->assertRedirect();

    $asset = Asset::query()->where('internal_code', 'CML-LAP-778')->firstOrFail();
    $file = $asset->files()->where('type', 'foto')->firstOrFail();

    expect($file->disk)->toBe('public');
    Storage::disk('public')->assertExists($file->path);
});

test('an invoice attached while editing an asset via AssetController::update is stored privately', function () {
    $invoice = UploadedFile::fake()->create('factura-edicion.pdf', 200, 'application/pdf');

    $this->actingAs($this->user)->put("/activos/{$this->asset->public_id}", [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'asset_type_id' => $this->assetType->id,
        'name' => $this->asset->name,
        'internal_code' => $this->asset->internal_code,
        'status' => $this->asset->status->value,
        'acquired_at' => $this->asset->acquired_at->toDateString(),
        'invoice_file' => $invoice,
    ])->assertRedirect();

    $file = $this->asset->files()->where('type', 'factura')->firstOrFail();

    expect($file->disk)->toBe('local');
    Storage::disk('local')->assertExists($file->path);
    Storage::disk('public')->assertMissing($file->path);

    // Nunca debe poder descargarse desde la ruta pública de /storage.
    $this->get(Storage::disk('public')->url($file->path))->assertNotFound();
});
