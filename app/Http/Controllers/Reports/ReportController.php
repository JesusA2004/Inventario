<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AssetStatus;
use App\Exports\AssetsExport;
use App\Exports\AuditsExport;
use App\Exports\DecommissionedAssetsExport;
use App\Exports\LoansExport;
use App\Exports\PartsExport;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Audit;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Department;
use App\Models\Loan;
use App\Models\Part;
use App\Models\ResponsiblePerson;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller implements HasMiddleware
{
    private const array TITLES = [
        'inventario' => 'Inventario general',
        'bajas' => 'Activos dados de baja',
        'prestamos' => 'Préstamos',
        'piezas' => 'Piezas y refacciones',
        'auditorias' => 'Auditorías',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-reportes'),
        ];
    }

    public function index(Request $request): Response
    {
        return Inertia::render('reportes/Index', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'reports' => collect(self::TITLES)->map(fn ($title, $key) => ['key' => $key, 'title' => $title])->values(),
            'inventoryFilterOptions' => [
                'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'company_id']),
                'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'company_id']),
                'assetTypes' => AssetType::query()->orderBy('name')->get(['id', 'name']),
                'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
                'responsiblePeople' => ResponsiblePerson::query()->orderBy('full_name')->get(['id', 'full_name']),
                'statuses' => AssetStatus::options(),
            ],
        ]);
    }

    public function excel(string $type, Request $request): BinaryFileResponse
    {
        $filename = "reporte-{$type}-".now()->format('Y-m-d').'.xlsx';

        return match ($type) {
            'inventario' => Excel::download(new AssetsExport($this->inventoryQuery($request)->get()), $filename),
            'bajas' => Excel::download(new DecommissionedAssetsExport($this->decommissionedQuery($request)->get()), $filename),
            'prestamos' => Excel::download(new LoansExport($this->loansQuery($request)->get()), $filename),
            'piezas' => Excel::download(new PartsExport($this->partsQuery($request)->get()), $filename),
            'auditorias' => Excel::download(new AuditsExport($this->auditsQuery($request)->get()), $filename),
            default => abort(404),
        };
    }

    public function pdf(string $type, Request $request): \Illuminate\Http\Response
    {
        abort_unless(array_key_exists($type, self::TITLES), 404);

        [$view, $data, $count] = match ($type) {
            'inventario' => ['pdf.reports.inventory', ['assets' => $this->inventoryQuery($request)->get()], null],
            'bajas' => ['pdf.reports.decommissioned', ['assets' => $this->decommissionedQuery($request)->get()], null],
            'prestamos' => ['pdf.reports.loans', ['loans' => $this->loansQuery($request)->get()], null],
            'piezas' => ['pdf.reports.parts', ['parts' => $this->partsQuery($request)->get()], null],
            'auditorias' => ['pdf.reports.audits', ['audits' => $this->auditsQuery($request)->get()], null],
        };

        $records = array_values($data)[0];
        $content = view($view, $data)->render();

        $pdf = Pdf::loadView('pdf.report-layout', [
            'title' => self::TITLES[$type],
            'generatedAt' => now()->translatedFormat('d \d\e F \d\e Y, H:i'),
            'generatedBy' => Auth::user()->name,
            'filtersSummary' => $this->filtersSummary($request),
            'content' => $content,
            'total' => $records->count(),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream("reporte-{$type}-".now()->format('Y-m-d').'.pdf');
    }

    private function filtersSummary(Request $request): string
    {
        $parts = [];
        if ($request->filled('company_id')) {
            $parts[] = 'Empresa: '.(Company::find($request->integer('company_id'))?->name ?? '—');
        }
        if ($request->filled('from')) {
            $parts[] = 'Desde: '.$request->string('from');
        }
        if ($request->filled('to')) {
            $parts[] = 'Hasta: '.$request->string('to');
        }
        if ($request->filled('branch_id')) {
            $parts[] = 'Sucursal: '.(Branch::find($request->integer('branch_id'))?->name ?? '—');
        }
        if ($request->filled('department_id')) {
            $parts[] = 'Área: '.(Department::find($request->integer('department_id'))?->name ?? '—');
        }
        if ($request->filled('responsible_id')) {
            $parts[] = 'Responsable: '.(ResponsiblePerson::find($request->integer('responsible_id'))?->full_name ?? '—');
        }
        if ($request->filled('asset_type_id')) {
            $parts[] = 'Tipo: '.(AssetType::find($request->integer('asset_type_id'))?->name ?? '—');
        }
        if ($request->filled('brand_id')) {
            $parts[] = 'Marca: '.(Brand::find($request->integer('brand_id'))?->name ?? '—');
        }
        if ($request->filled('status')) {
            $parts[] = 'Estatus: '.(AssetStatus::tryFrom($request->string('status')->toString())?->label() ?? '—');
        }
        if ($request->filled('in_inventory')) {
            $parts[] = $request->boolean('in_inventory') ? 'Solo en inventario' : 'Solo dados de baja';
        }

        return implode(' · ', $parts);
    }

    /**
     * Filtros compartidos por la vista en tiempo real, el Excel y el PDF del
     * reporte de inventario, para garantizar que las tres salidas reflejen
     * exactamente el mismo conjunto de datos. Las columnas van calificadas
     * con "assets." porque branches/departments también tienen company_id,
     * y los métodos que agregan (groupBy + join) generarían una columna
     * ambigua si no se calificaran aquí.
     *
     * @return Builder<Asset>
     */
    private function inventoryBaseQuery(Request $request): Builder
    {
        return Asset::query()
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('assets.company_id', $v))
            ->when($request->integer('branch_id'), fn ($q, $v) => $q->where('assets.branch_id', $v))
            ->when($request->integer('department_id'), fn ($q, $v) => $q->where('assets.department_id', $v))
            ->when($request->integer('responsible_id'), fn ($q, $v) => $q->where('assets.current_responsible_id', $v))
            ->when($request->integer('asset_type_id'), fn ($q, $v) => $q->where('assets.asset_type_id', $v))
            ->when($request->integer('brand_id'), fn ($q, $v) => $q->where('assets.brand_id', $v))
            ->when($request->string('status')->toString(), fn ($q, $v) => $q->where('assets.status', $v))
            ->when($request->filled('in_inventory'), fn ($q) => $q->where('assets.in_inventory', $request->boolean('in_inventory')))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('assets.acquired_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('assets.acquired_at', '<=', $v));
    }

    /**
     * @return Builder<Asset>
     */
    private function inventoryQuery(Request $request): Builder
    {
        return $this->inventoryBaseQuery($request)
            ->with(['company', 'branch', 'department', 'brand', 'assetType', 'currentResponsible'])
            ->orderBy('internal_code');
    }

    /**
     * Datos en vivo para el panel de "Inventario en tiempo real": el mismo
     * conjunto filtrado que consumen excel()/pdf(), agregado para las
     * gráficas y con una muestra de filas para la vista previa.
     */
    public function inventoryData(Request $request): JsonResponse
    {
        $total = $this->inventoryBaseQuery($request)->count();

        $rows = $this->inventoryBaseQuery($request)
            ->with(['company:id,name', 'branch:id,name', 'department:id,name', 'brand:id,name', 'assetType:id,name', 'currentResponsible:id,full_name'])
            ->orderBy('internal_code')
            ->limit(30)
            ->get()
            ->map(function (Asset $asset): array {
                return [
                    'id' => $asset->id,
                    'public_id' => $asset->public_id,
                    'internal_code' => $asset->internal_code,
                    'name' => $asset->name,
                    'company' => $asset->company?->name,
                    'branch' => $asset->branch?->name,
                    'department' => $asset->department?->name,
                    'brand' => $asset->brand?->name,
                    'asset_type' => $asset->assetType?->name,
                    'responsible' => $asset->currentResponsible?->full_name,
                    'status' => ['label' => $asset->status->label(), 'color' => $asset->status->color()],
                    'in_inventory' => $asset->in_inventory,
                ];
            });

        $byStatus = $this->inventoryBaseQuery($request)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function (Asset $row): array {
                $status = $row->status instanceof AssetStatus ? $row->status : AssetStatus::tryFrom((string) $row->status);

                return ['label' => $status?->label() ?? (string) $row->status, 'color' => $status?->color() ?? 'gray', 'total' => (int) $row->getAttribute('total')];
            });

        $byBranch = $this->inventoryBaseQuery($request)
            ->join('branches', 'branches.id', '=', 'assets.branch_id')
            ->select('branches.name', DB::raw('count(*) as total'))
            ->groupBy('branches.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byType = $this->inventoryBaseQuery($request)
            ->join('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
            ->select('asset_types.name', DB::raw('count(*) as total'))
            ->groupBy('asset_types.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byCompany = $this->inventoryBaseQuery($request)
            ->join('companies', 'companies.id', '=', 'assets.company_id')
            ->select('companies.name', DB::raw('count(*) as total'))
            ->groupBy('companies.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return response()->json([
            'total' => $total,
            'inInventory' => $this->inventoryBaseQuery($request)->where('assets.in_inventory', true)->count(),
            'rows' => $rows,
            'byStatus' => $byStatus,
            'byBranch' => $byBranch,
            'byType' => $byType,
            'byCompany' => $byCompany,
        ]);
    }

    /**
     * @return Builder<Asset>
     */
    private function decommissionedQuery(Request $request): Builder
    {
        return Asset::query()
            ->with(['company', 'branch'])
            ->where('in_inventory', false)
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('decommissioned_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('decommissioned_at', '<=', $v))
            ->orderByDesc('decommissioned_at');
    }

    /**
     * @return Builder<Loan>
     */
    private function loansQuery(Request $request): Builder
    {
        return Loan::query()
            ->with(['asset', 'company', 'assignedTo', 'deliveredBy', 'receivedBy'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('loan_date', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('loan_date', '<=', $v))
            ->orderByDesc('loan_date');
    }

    /**
     * @return Builder<Part>
     */
    private function partsQuery(Request $request): Builder
    {
        return Part::query()
            ->with(['company', 'brand', 'relatedAsset'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->orderBy('internal_code');
    }

    /**
     * @return Builder<Audit>
     */
    private function auditsQuery(Request $request): Builder
    {
        return Audit::query()
            ->with(['company', 'branch', 'items'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('started_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('started_at', '<=', $v))
            ->orderByDesc('started_at');
    }
}
