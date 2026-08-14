<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\ResponsiblePersonRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ResponsiblePersonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-catalogos', only: ['index']),
            new Middleware('permission:gestionar-catalogos', except: ['index', 'store']),
        ];
    }

    public function index(Request $request): Response
    {
        $responsiblePeople = ResponsiblePerson::query()
            ->with(['company:id,name,code', 'branch:id,name', 'department:id,name'])
            ->withCount('assetsInCharge')
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->orderBy('full_name')
            ->get();

        return Inertia::render('responsables/Index', [
            'responsiblePeople' => $responsiblePeople,
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'filters' => $request->only('q', 'company_id'),
        ]);
    }

    /**
     * Used by the "+ Nuevo responsable" quick-create dialog embedded in the
     * asset form, so it must respond with JSON instead of redirecting.
     */
    public function store(ResponsiblePersonRequest $request): RedirectResponse|JsonResponse
    {
        $responsible = ResponsiblePerson::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($responsible);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable creado correctamente.']);

        return back();
    }

    public function update(ResponsiblePersonRequest $request, ResponsiblePerson $responsable): RedirectResponse
    {
        $responsable->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable actualizado correctamente.']);

        return back();
    }

    public function destroy(ResponsiblePerson $responsable): RedirectResponse
    {
        if ($responsable->assetsInCharge()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos asignados. Desactívalo en su lugar.',
            ]);

            return back();
        }

        $responsable->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable eliminado.']);

        return back();
    }
}
