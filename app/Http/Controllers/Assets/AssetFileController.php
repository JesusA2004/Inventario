<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetFileType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Services\AssetFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetFileController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AssetFileService $files) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:editar-activos', only: ['store', 'destroy']),
            new Middleware('permission:ver-activos', only: ['download']),
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

        $this->files->storeForType($asset, $request->file('file'), AssetFileType::from($data['type']), $request->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Archivo subido correctamente.']);

        return back();
    }

    public function destroy(Asset $asset, AssetFile $file): RedirectResponse
    {
        abort_unless($file->asset_id === $asset->id, 404);

        $this->files->delete($file);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Archivo eliminado.']);

        return back();
    }

    /**
     * Único punto de descarga para facturas/documentos privados. Verifica
     * autenticación (vía middleware), permiso para ver activos, y que el
     * archivo realmente pertenece al activo solicitado en la URL, antes de
     * transmitir el contenido — nunca se revela la ruta física en disco.
     */
    public function download(Asset $asset, AssetFile $file): StreamedResponse
    {
        abort_unless($file->asset_id === $asset->id, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
