<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssetFileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:editar-activos'),
        ];
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
