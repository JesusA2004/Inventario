<?php

namespace App\Services;

use App\Enums\AssetFileType;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Único punto de entrada para subir/borrar archivos de un activo. Antes
 * existían dos implementaciones distintas (AssetController::storeFiles() y
 * AssetFileController::store()) que decidían el disco por separado; una de
 * ellas guardaba las facturas en el disco público por accidente. Ahora
 * ambos controladores pasan por aquí, así la regla "foto = pública,
 * factura/documento = privado" vive en un solo lugar.
 */
class AssetFileService
{
    public function storePhoto(Asset $asset, UploadedFile $file, ?int $uploadedBy): AssetFile
    {
        return $this->store($asset, $file, AssetFileType::Foto, $uploadedBy);
    }

    public function storeInvoice(Asset $asset, UploadedFile $file, ?int $uploadedBy): AssetFile
    {
        return $this->store($asset, $file, AssetFileType::Factura, $uploadedBy);
    }

    public function storeForType(Asset $asset, UploadedFile $file, AssetFileType $type, ?int $uploadedBy): AssetFile
    {
        return $this->store($asset, $file, $type, $uploadedBy);
    }

    public function delete(AssetFile $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }

    private function store(Asset $asset, UploadedFile $file, AssetFileType $type, ?int $uploadedBy): AssetFile
    {
        $disk = $this->diskFor($type);
        $path = $file->store('assets/'.$asset->id.'/'.$type->value, $disk);

        return $asset->files()->create([
            'type' => $type,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $uploadedBy,
        ]);
    }

    private function diskFor(AssetFileType $type): string
    {
        return $type === AssetFileType::Foto ? 'public' : 'local';
    }
}
