<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller implements HasMiddleware
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
        $brands = Brand::query()
            ->withCount(['assets', 'parts'])
            ->when($request->string('q')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('brands/Index', [
            'brands' => $brands,
            'filters' => $request->only('q'),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse|JsonResponse
    {
        $brand = Brand::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($brand);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca creada correctamente.']);

        return back();
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca actualizada correctamente.']);

        return back();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->assets()->exists() || $brand->parts()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos o piezas relacionadas. Desactívala en su lugar.',
            ]);

            return back();
        }

        $brand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca eliminada.']);

        return back();
    }
}
