<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\AssetTypeRequest;
use App\Models\AssetType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AssetTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-catalogos', only: ['index']),
            new Middleware('permission:gestionar-catalogos', except: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $assetTypes = AssetType::query()
            ->withCount(['assets'])
            ->when($request->string('q')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('asset-types/Index', [
            'assetTypes' => $assetTypes,
            'filters' => $request->only('q'),
        ]);
    }

    public function store(AssetTypeRequest $request): RedirectResponse|JsonResponse
    {
        $assetType = AssetType::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($assetType);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tipo de activo creado correctamente.']);

        return back();
    }

    public function update(AssetTypeRequest $request, AssetType $assetType): RedirectResponse
    {
        $assetType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tipo de activo actualizado correctamente.']);

        return back();
    }

    public function destroy(AssetType $assetType): RedirectResponse
    {
        if ($assetType->assets()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos relacionados. Desactívalo en su lugar.',
            ]);

            return back();
        }

        $assetType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tipo de activo eliminado.']);

        return back();
    }
}
