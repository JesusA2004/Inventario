<?php

namespace App\Http\Controllers\Reports;

use App\Exports\AssetsExport;
use App\Exports\AuditsExport;
use App\Exports\DecommissionedAssetsExport;
use App\Exports\LoansExport;
use App\Exports\PartsExport;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Audit;
use App\Models\Company;
use App\Models\Loan;
use App\Models\Part;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
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

        return implode(' · ', $parts);
    }

    private function inventoryQuery(Request $request)
    {
        return Asset::query()
            ->with(['company', 'branch', 'department', 'brand', 'assetType', 'currentResponsible'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('acquired_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('acquired_at', '<=', $v))
            ->orderBy('internal_code');
    }

    private function decommissionedQuery(Request $request)
    {
        return Asset::query()
            ->with(['company', 'branch'])
            ->where('in_inventory', false)
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('decommissioned_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('decommissioned_at', '<=', $v))
            ->orderByDesc('decommissioned_at');
    }

    private function loansQuery(Request $request)
    {
        return Loan::query()
            ->with(['asset', 'company', 'assignedTo', 'deliveredBy', 'receivedBy'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('loan_date', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('loan_date', '<=', $v))
            ->orderByDesc('loan_date');
    }

    private function partsQuery(Request $request)
    {
        return Part::query()
            ->with(['company', 'brand', 'relatedAsset'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->orderBy('internal_code');
    }

    private function auditsQuery(Request $request)
    {
        return Audit::query()
            ->with(['company', 'branch', 'items'])
            ->when($request->integer('company_id'), fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->date('from'), fn ($q, $v) => $q->whereDate('started_at', '>=', $v))
            ->when($request->date('to'), fn ($q, $v) => $q->whereDate('started_at', '<=', $v))
            ->orderByDesc('started_at');
    }
}
