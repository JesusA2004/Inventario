<?php

namespace App\Http\Controllers\Audits;

use App\Enums\AuditItemStatus;
use App\Enums\AuditStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Audit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-auditorias', only: ['index', 'show']),
            new Middleware('permission:gestionar-auditorias', except: ['index', 'show']),
        ];
    }

    public function index(Request $request): Response
    {
        $audits = Audit::query()
            ->with(['company:id,name', 'branch:id,name', 'department:id,name', 'creator:id,name'])
            ->withCount([
                'items',
                'items as found_count' => fn ($query) => $query->where('status', AuditItemStatus::Encontrado),
                'items as pending_count' => fn ($query) => $query->where('status', AuditItemStatus::Pendiente),
            ])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('started_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('auditorias/Index', [
            'audits' => $audits,
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('auditorias/Create', [
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);

        $audit = DB::transaction(function () use ($data, $branch, $request) {
            $audit = Audit::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'department_id' => $data['department_id'] ?? null,
                'name' => ($data['name'] ?? null) ?: "Auditoría {$branch->name} — ".now()->translatedFormat('d M Y'),
                'started_at' => now(),
                'status' => AuditStatus::EnProgreso,
                'created_by' => $request->user()->id,
            ]);

            $assetIds = Asset::query()
                ->where('company_id', $data['company_id'])
                ->where('branch_id', $data['branch_id'])
                ->when($data['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
                ->where('in_inventory', true)
                ->pluck('id');

            $now = now();
            $rows = $assetIds->map(fn ($assetId) => [
                'audit_id' => $audit->id,
                'asset_id' => $assetId,
                'status' => AuditItemStatus::Pendiente->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                DB::table('audit_items')->insert($rows);
            }

            return $audit;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Auditoría creada correctamente.']);

        return redirect()->route('audits.show', $audit);
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'company:id,name', 'branch:id,name', 'department:id,name', 'creator:id,name',
            'items.asset:id,public_id,internal_code,name,branch_id,department_id,current_responsible_id',
            'items.asset.branch:id,name',
            'items.asset.department:id,name',
            'items.asset.currentResponsible:id,full_name',
            'items.foundBranch:id,name',
            'items.foundDepartment:id,name',
            'items.foundResponsible:id,full_name',
            'items.checkedBy:id,name',
        ]);

        $items = $audit->items->map(fn ($item) => [
            'id' => $item->id,
            'status' => ['value' => $item->status->value, 'label' => $item->status->label(), 'color' => $item->status->color()],
            'comment' => $item->comment,
            'checked_at' => $item->checked_at?->toISOString(),
            'checked_by' => $item->checkedBy?->name,
            'asset' => [
                'id' => $item->asset->id,
                'public_id' => $item->asset->public_id,
                'internal_code' => $item->asset->internal_code,
                'name' => $item->asset->name,
                'expected_branch' => $item->asset->branch?->name,
                'expected_department' => $item->asset->department?->name,
                'expected_responsible' => $item->asset->currentResponsible?->full_name,
            ],
            'found_branch' => $item->foundBranch?->name,
            'found_department' => $item->foundDepartment?->name,
            'found_responsible' => $item->foundResponsible?->full_name,
        ]);

        $total = $items->count();
        $found = $items->where('status.value', AuditItemStatus::Encontrado->value)->count();
        $pending = $items->where('status.value', AuditItemStatus::Pendiente->value)->count();
        $missing = $items->where('status.value', AuditItemStatus::NoEncontrado->value)->count();
        $differences = $total - $found - $pending - $missing;

        return Inertia::render('auditorias/Show', [
            'audit' => [
                'id' => $audit->id,
                'name' => $audit->name,
                'status' => ['value' => $audit->status->value, 'label' => $audit->status->label(), 'color' => $audit->status->color()],
                'started_at' => $audit->started_at?->toISOString(),
                'finished_at' => $audit->finished_at?->toISOString(),
                'company' => $audit->company?->name,
                'branch' => $audit->branch?->name,
                'department' => $audit->department?->name,
                'creator' => $audit->creator?->name,
            ],
            'items' => $items,
            'stats' => [
                'total' => $total,
                'found' => $found,
                'pending' => $pending,
                'missing' => $missing,
                'differences' => max(0, $differences),
                'percent' => $total > 0 ? (int) round((($total - $pending) / $total) * 100) : 0,
            ],
            'itemStatusOptions' => AuditItemStatus::options(),
        ]);
    }

    public function finish(Audit $audit): RedirectResponse
    {
        $audit->items()->where('status', AuditItemStatus::Pendiente)->update(['status' => AuditItemStatus::NoEncontrado->value]);

        $audit->update([
            'status' => AuditStatus::Finalizada,
            'finished_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Auditoría finalizada. Los pendientes se marcaron como no encontrados.']);

        return redirect()->route('audits.show', $audit);
    }
}
