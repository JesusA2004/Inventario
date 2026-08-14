<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetFileType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class AssetFileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:editar-activos'),
        ];
    }

    /**
     * Sube una foto o factura directamente desde la ficha del activo (sin
     * pasar por el formulario completo de edición). Pensado para capturar
     * la foto con la cámara del teléfono en el momento.
     */
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', new Enum(AssetFileType::class)],
            'file' => $request->string('type')->toString() === AssetFileType::Foto->value
                ? ['required', 'image', 'max:5120']
                : ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('assets/'.$asset->id.'/'.$data['type'], 'public');

        $asset->files()->create([
            'type' => $data['type'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Archivo subido correctamente.']);

        return back();
    }

    public function destroy(Asset $asset, AssetFile $file): RedirectResponse
    {
        abort_unless($file->asset_id === $asset->id, 404);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Archivo eliminado.']);

        return back();
    }
}
