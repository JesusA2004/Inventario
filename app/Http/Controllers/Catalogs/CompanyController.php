<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller implements HasMiddleware
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
        $companies = Company::query()
            ->withCount(['branches', 'assets'])
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('companies/Index', [
            'companies' => $companies,
            'filters' => $request->only('q'),
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        Company::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa creada correctamente.']);

        return back();
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        $company->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa actualizada correctamente.']);

        return back();
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->branches()->exists() || $company->assets()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene sucursales o activos relacionados. Desactívala en su lugar.',
            ]);

            return back();
        }

        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa eliminada.']);

        return back();
    }
}
