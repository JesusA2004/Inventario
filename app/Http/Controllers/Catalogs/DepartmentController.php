<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\DepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller implements HasMiddleware
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
        $departments = Department::query()
            ->with('company:id,name,code')
            ->withCount('assets')
            ->when($request->string('q')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('departments/Index', [
            'departments' => $departments,
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only('q'),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse|JsonResponse
    {
        $department = Department::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($department);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área creada correctamente.']);

        return back();
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área actualizada correctamente.']);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->assets()->exists() || $department->responsiblePeople()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos o responsables relacionados. Desactívala en su lugar.',
            ]);

            return back();
        }

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área eliminada.']);

        return back();
    }
}
