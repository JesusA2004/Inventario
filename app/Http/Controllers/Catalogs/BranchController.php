<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\BranchRequest;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller implements HasMiddleware
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
        $branches = Branch::query()
            ->with('company:id,name,code')
            ->withCount('assets')
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        return Inertia::render('branches/Index', [
            'branches' => $branches,
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only('q', 'company_id'),
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse|JsonResponse
    {
        $branch = Branch::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($branch);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal creada correctamente.']);

        return back();
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal actualizada correctamente.']);

        return back();
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->assets()->exists() || $branch->responsiblePeople()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos o responsables relacionados. Desactívala en su lugar.',
            ]);

            return back();
        }

        $branch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal eliminada.']);

        return back();
    }
}
