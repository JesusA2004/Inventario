<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\LoanStatus;
use App\Enums\PartStatus;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Loan;
use App\Models\Part;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        [$companyId, $branchId, $months] = $this->parseFilters($request);

        return Inertia::render('Dashboard', [
            ...$this->buildData($companyId, $branchId, $months),
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'months' => $months,
            ],
            'filterOptions' => [
                'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
                'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            ],
        ]);
    }

    /**
     * Mismos datos que index(), en JSON, para refrescar el dashboard en
     * tiempo real (debounced) sin recargar la página al cambiar filtros.
     */
    public function data(Request $request): JsonResponse
    {
        [$companyId, $branchId, $months] = $this->parseFilters($request);

        return response()->json($this->buildData($companyId, $branchId, $months));
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: int}
     */
    private function parseFilters(Request $request): array
    {
        return [
            $request->integer('company_id') ?: null,
            $request->integer('branch_id') ?: null,
            max(3, min(24, $request->integer('months', 6))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildData(?int $companyId, ?int $branchId, int $months): array
    {
        $baseQuery = Asset::query()
            ->when($companyId, fn ($query) => $query->where('assets.company_id', $companyId))
            ->when($branchId, fn ($query) => $query->where('assets.branch_id', $branchId));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'inInventory' => (clone $baseQuery)->where('in_inventory', true)->count(),
            'decommissioned' => (clone $baseQuery)->where('in_inventory', false)->count(),
            'damaged' => (clone $baseQuery)->where('status', AssetStatus::Danado)->count(),
            'inReview' => (clone $baseQuery)->where('status', AssetStatus::EnRevision)->count(),
            'activeLoans' => Loan::query()
                ->where('status', LoanStatus::Prestado)
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when($branchId, fn ($query) => $query->whereHas('asset', fn ($asset) => $asset->where('branch_id', $branchId)))
                ->count(),
            'overdueLoans' => Loan::query()
                ->where('status', LoanStatus::Prestado)
                ->where('expected_return_date', '<', now())
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when($branchId, fn ($query) => $query->whereHas('asset', fn ($asset) => $asset->where('branch_id', $branchId)))
                ->count(),
            'availableParts' => Part::query()
                ->where('in_inventory', true)
                ->where('status', PartStatus::Funcional)
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->count(),
        ];

        $byCompany = (clone $baseQuery)
            ->join('companies', 'companies.id', '=', 'assets.company_id')
            ->select('companies.name', DB::raw('count(*) as total'))
            ->groupBy('companies.name')
            ->orderByDesc('total')
            ->get();

        $byType = (clone $baseQuery)
            ->join('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
            ->select('asset_types.name', DB::raw('count(*) as total'))
            ->groupBy('asset_types.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byStatus = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $status = $row->status instanceof AssetStatus ? $row->status : AssetStatus::tryFrom((string) $row->status);

                return [
                    'label' => $status?->label() ?? $row->status,
                    'color' => $status?->color() ?? 'gray',
                    'total' => $row->total,
                ];
            });

        $byBranch = (clone $baseQuery)
            ->join('branches', 'branches.id', '=', 'assets.branch_id')
            ->select('branches.name', DB::raw('count(*) as total'))
            ->groupBy('branches.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byDepartment = (clone $baseQuery)
            ->join('departments', 'departments.id', '=', 'assets.department_id')
            ->select('departments.name', DB::raw('count(*) as total'))
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byLoanStatus = Loan::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn ($query) => $query->whereHas('asset', fn ($asset) => $asset->where('branch_id', $branchId)))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $status = $row->status instanceof LoanStatus ? $row->status : LoanStatus::tryFrom((string) $row->status);

                return ['label' => $status?->label() ?? (string) $row->status, 'color' => $status?->color() ?? 'gray', 'total' => $row->total];
            });

        $byPartStatus = Part::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $status = $row->status instanceof PartStatus ? $row->status : PartStatus::tryFrom((string) $row->status);

                return ['label' => $status?->label() ?? (string) $row->status, 'color' => $status?->color() ?? 'gray', 'total' => $row->total];
            });

        $byResponsible = (clone $baseQuery)
            ->join('responsible_people', 'responsible_people.id', '=', 'assets.current_responsible_id')
            ->select('responsible_people.full_name', DB::raw('count(*) as total'))
            ->groupBy('responsible_people.full_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'stats' => $stats,
            'charts' => [
                'byCompany' => $byCompany,
                'byType' => $byType,
                'byStatus' => $byStatus,
                'byBranch' => $byBranch,
                'byDepartment' => $byDepartment,
                'byLoanStatus' => $byLoanStatus,
                'byPartStatus' => $byPartStatus,
                'byResponsible' => $byResponsible,
                'monthly' => $this->monthlySeries($companyId, $branchId, $months),
            ],
        ];
    }

    /**
     * @return array<int, array{month: string, altas: int, bajas: int}>
     */
    private function monthlySeries(?int $companyId, ?int $branchId, int $months): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $altas = Asset::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('acquired_at', '>=', $start)
            ->pluck('acquired_at')
            ->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        $bajas = Asset::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereNotNull('decommissioned_at')
            ->where('decommissioned_at', '>=', $start)
            ->pluck('decommissioned_at')
            ->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $date = (clone $start)->addMonths($i);
            $key = $date->format('Y-m');

            $series[] = [
                'month' => Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y'),
                'altas' => (int) ($altas[$key] ?? 0),
                'bajas' => (int) ($bajas[$key] ?? 0),
            ];
        }

        return $series;
    }
}
