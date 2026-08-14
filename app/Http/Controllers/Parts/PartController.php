<?php

namespace App\Http\Controllers\Parts;

use App\Enums\PartStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parts\PartRequest;
use App\Http\Resources\PartResource;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Part;
use App\Models\ResponsiblePerson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-piezas', only: ['index']),
            new Middleware('permission:gestionar-piezas', except: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $parts = Part::query()
            ->with(['company:id,name', 'branch:id,name', 'brand:id,name', 'relatedAsset:id,public_id,internal_code'])
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('part_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->when($request->string('status')->toString(), fn ($query, $value) => $query->where('status', $value))
            ->when($request->filled('assembled'), fn ($query) => $query->where('assembled', $request->boolean('assembled')))
            ->when($request->filled('in_inventory'), fn ($query) => $query->where('in_inventory', $request->boolean('in_inventory')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Part $part) => (new PartResource($part))->resolve());

        return Inertia::render('piezas/Index', [
            'parts' => $parts,
            'filters' => $request->only(['q', 'company_id', 'status', 'assembled', 'in_inventory']),
            'filterOptions' => [
                'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
                'statuses' => PartStatus::options(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('piezas/Create', [
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function store(PartRequest $request): RedirectResponse
    {
        $part = Part::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Pieza {$part->internal_code} registrada correctamente."]);

        return redirect()->route('parts.index');
    }

    public function edit(Part $part): Response
    {
        $part->load('relatedAsset:id,public_id,internal_code,name');

        return Inertia::render('piezas/Edit', [
            'part' => $part,
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function update(PartRequest $request, Part $part): RedirectResponse
    {
        $part->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pieza actualizada correctamente.']);

        return redirect()->route('parts.index');
    }

    public function decommission(Request $request, Part $part): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $part->update([
            'in_inventory' => false,
            'status' => PartStatus::Baja,
            'decommissioned_at' => now()->toDateString(),
            'decommission_reason' => $data['reason'],
            'notes' => $part->notes ? $part->notes."\n".($data['notes'] ?? '') : ($data['notes'] ?? null),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pieza dada de baja.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']),
            'responsiblePeople' => ResponsiblePerson::query()->active()->orderBy('full_name')->get(['id', 'full_name', 'company_id']),
            'statuses' => PartStatus::options(),
        ];
    }
}
