<?php

namespace App\Http\Controllers\Loans;

use App\Enums\AssetStatus;
use App\Enums\LoanStatus;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Loan;
use App\Models\ResponsiblePerson;
use App\Services\AssetMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AssetMovementService $movements) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-prestamos', only: ['index']),
            new Middleware('permission:gestionar-prestamos', except: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $loans = Loan::query()
            ->with(['asset:id,public_id,internal_code,name', 'company:id,name', 'assignedTo:id,full_name', 'deliveredBy:id,full_name', 'receivedBy:id,full_name'])
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->whereHas('asset', function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status')->toString(), function ($query, $status) {
                if ($status === 'vencido') {
                    $query->where('status', LoanStatus::Prestado)->where('expected_return_date', '<', now());
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->orderByDesc('loan_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Loan $loan) => (new LoanResource($loan))->resolve());

        return Inertia::render('prestamos/Index', [
            'loans' => $loans,
            'filters' => $request->only(['q', 'status', 'company_id']),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'active' => Loan::query()->where('status', LoanStatus::Prestado)->count(),
                'overdue' => Loan::query()->where('status', LoanStatus::Prestado)->where('expected_return_date', '<', now())->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $preselectedAsset = null;

        if ($request->filled('asset_id')) {
            $preselectedAsset = Asset::query()
                ->where('public_id', $request->string('asset_id'))
                ->with('company:id,name')
                ->first(['id', 'public_id', 'internal_code', 'name', 'company_id']);
        }

        return Inertia::render('prestamos/Create', [
            'preselectedAsset' => $preselectedAsset,
        ]);
    }

    public function responsiblesForCompany(Request $request, Company $company): JsonResponse
    {
        return response()->json(
            ResponsiblePerson::query()->active()->where('company_id', $company->id)->orderBy('full_name')->get(['id', 'full_name'])
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'assigned_to_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'delivered_by_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'received_by_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'loan_date' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'delivered_confirmed' => ['boolean'],
            'received_confirmed' => ['boolean'],
            'origin' => ['nullable', 'string', 'in:asset,index'],
        ]);

        $loan = DB::transaction(function () use ($data, $request) {
            $asset = Asset::query()->whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();

            $hasActiveLoan = Loan::query()
                ->where('asset_id', $asset->id)
                ->where('status', LoanStatus::Prestado)
                ->exists();

            if ($hasActiveLoan) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Este activo ya tiene un préstamo activo. Debe devolverse antes de registrar uno nuevo.',
                ]);
            }

            foreach ([
                'assigned_to_responsible_id' => 'assigned_to_responsible_id',
                'delivered_by_responsible_id' => 'delivered_by_responsible_id',
                'received_by_responsible_id' => 'received_by_responsible_id',
            ] as $field) {
                if (empty($data[$field])) {
                    continue;
                }

                $responsible = ResponsiblePerson::find($data[$field]);

                if ($responsible && $responsible->company_id !== $asset->company_id) {
                    throw ValidationException::withMessages([
                        $field => 'El responsable seleccionado no pertenece a la empresa del activo.',
                    ]);
                }
            }

            $loan = Loan::create([
                'asset_id' => $asset->id,
                'company_id' => $asset->company_id,
                'assigned_to_responsible_id' => $data['assigned_to_responsible_id'] ?? null,
                'delivered_by_responsible_id' => $data['delivered_by_responsible_id'] ?? null,
                'received_by_responsible_id' => $data['received_by_responsible_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'loan_date' => $data['loan_date'],
                'expected_return_date' => $data['expected_return_date'] ?? null,
                'delivered_confirmed' => $data['delivered_confirmed'] ?? false,
                'received_confirmed' => $data['received_confirmed'] ?? false,
                'status' => LoanStatus::Prestado,
                'created_by' => $request->user()->id,
            ]);

            $asset->update(['status' => AssetStatus::Activo]);

            return $loan;
        });

        $asset = Asset::query()->whereKey($data['asset_id'])->firstOrFail();

        $this->movements->log(
            $asset,
            MovementType::Prestamo,
            comment: 'Préstamo registrado.'.($data['reason'] ?? '' ? ' Motivo: '.$data['reason'] : ''),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préstamo registrado correctamente.']);

        if (($data['origin'] ?? null) === 'asset') {
            return redirect()->route('assets.show', $asset);
        }

        return redirect()->route('loans.index');
    }

    public function returnLoan(Request $request, Loan $loan): RedirectResponse
    {
        $data = $request->validate([
            'actual_return_date' => ['required', 'date'],
            'return_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $loan->update([
            'status' => LoanStatus::Devuelto,
            'actual_return_date' => $data['actual_return_date'],
            'return_notes' => $data['return_notes'] ?? null,
        ]);

        $asset = Asset::query()->whereKey($loan->asset_id)->firstOrFail();

        $this->movements->log(
            $asset,
            MovementType::Devolucion,
            comment: 'Devolución registrada.'.(($data['return_notes'] ?? null) ? ' '.$data['return_notes'] : ''),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Devolución registrada correctamente.']);

        return back();
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        $loan->update(['status' => LoanStatus::Cancelado]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préstamo cancelado.']);

        return back();
    }
}
