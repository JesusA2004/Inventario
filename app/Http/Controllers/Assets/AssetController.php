<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetFileType;
use App\Enums\AssetStatus;
use App\Enums\DecommissionReason;
use App\Enums\MovementType;
use App\Exports\AssetsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\AssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Services\AssetCodeService;
use App\Services\AssetMovementService;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AssetController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetMovementService $movements,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-activos', only: ['index', 'show', 'export', 'search', 'checkSerial', 'qrZip', 'filteredIds']),
            new Middleware('permission:crear-activos', only: ['create', 'store', 'generateCode']),
            new Middleware('permission:editar-activos', only: ['edit', 'update']),
        ];
    }

    /**
     * Lightweight asset lookup used by other modules (préstamos, piezas)
     * to link a record to an existing asset without loading the full list.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $assets = Asset::query()
            ->when($request->boolean('in_inventory_only'), fn ($query) => $query->where('in_inventory', true))
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('internal_code')
            ->limit(20)
            ->get(['id', 'public_id', 'internal_code', 'name', 'company_id']);

        return response()->json($assets);
    }

    /**
     * Devuelve los IDs de TODOS los activos que coinciden con los filtros
     * actuales de /activos (no solo la página visible), para poder
     * distinguir "seleccionar esta página" de "seleccionar los N
     * resultados filtrados" antes de generar etiquetas o el ZIP de QR.
     */
    public function filteredIds(Request $request): JsonResponse
    {
        $ids = $this->filteredQuery($request)->limit(1000)->pluck('id');

        return response()->json(['ids' => $ids, 'truncated' => $ids->count() >= 1000]);
    }

    public function index(Request $request): Response
    {
        $assets = $this->filteredQuery($request)
            ->with(['company:id,name,code', 'branch:id,name', 'department:id,name', 'brand:id,name', 'assetType:id,name', 'currentResponsible:id,full_name'])
            ->orderBy($request->string('sort', 'created_at')->toString(), $request->string('direction', 'desc')->toString())
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Asset $asset) => (new AssetResource($asset))->resolve());

        return Inertia::render('activos/Index', [
            'assets' => $assets,
            'filters' => $request->only([
                'q', 'company_id', 'branch_id', 'department_id', 'asset_type_id',
                'brand_id', 'responsible_id', 'status', 'in_inventory', 'from', 'to', 'sort', 'direction',
            ]),
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $assets = $this->filteredQuery($request)
            ->with(['company', 'branch', 'department', 'brand', 'assetType', 'currentResponsible'])
            ->orderBy('internal_code')
            ->get();

        return Excel::download(
            new AssetsExport($assets),
            'inventario-activos-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Descarga masiva de códigos QR en un .zip. Acepta una selección
     * explícita de IDs (checkboxes marcados) o, si no se envían, genera el
     * ZIP para el conjunto completo filtrado actual (botón "seleccionar
     * todos los resultados filtrados").
     */
    public function qrZip(Request $request): BinaryFileResponse
    {
        $request->validate([
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer'],
        ]);

        $assets = $request->filled('asset_ids')
            ? Asset::query()->whereIn('id', $request->array('asset_ids'))->get()
            : $this->filteredQuery($request)->limit(500)->get();

        abort_if($assets->isEmpty(), 422, 'No hay activos para generar el ZIP de códigos QR.');

        set_time_limit(120);

        $tempPath = tempnam(sys_get_temp_dir(), 'qr-zip-');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedNames = [];

        foreach ($assets as $asset) {
            $png = $this->qrCodeService->png($this->qrCodeService->publicUrl($asset), 600);
            $baseName = Str::slug($asset->internal_code).'-'.Str::slug($asset->name).'-qr';

            $name = "{$baseName}.png";
            $suffix = 2;

            while (in_array($name, $usedNames, true)) {
                $name = "{$baseName}-{$suffix}.png";
                $suffix++;
            }

            $usedNames[] = $name;
            $zip->addFromString($name, $png->getString());
        }

        $zip->close();

        return response()
            ->download($tempPath, 'qr-inventario-'.now()->format('Y-m-d').'.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * @return Builder<Asset>
     */
    private function filteredQuery(Request $request): Builder
    {
        return Asset::query()
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('currentResponsible', fn ($responsible) => $responsible->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->when($request->integer('branch_id'), fn ($query, $value) => $query->where('branch_id', $value))
            ->when($request->integer('department_id'), fn ($query, $value) => $query->where('department_id', $value))
            ->when($request->integer('asset_type_id'), fn ($query, $value) => $query->where('asset_type_id', $value))
            ->when($request->integer('brand_id'), fn ($query, $value) => $query->where('brand_id', $value))
            ->when($request->integer('responsible_id'), fn ($query, $value) => $query->where('current_responsible_id', $value))
            ->when($request->string('status')->toString(), fn ($query, $value) => $query->where('status', $value))
            ->when($request->filled('in_inventory'), fn ($query) => $query->where('in_inventory', $request->boolean('in_inventory')))
            ->when($request->date('from'), fn ($query, $value) => $query->whereDate('acquired_at', '>=', $value))
            ->when($request->date('to'), fn ($query, $value) => $query->whereDate('acquired_at', '<=', $value));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('activos/Create', [
            'formOptions' => $this->formOptions(),
            'prefill' => $request->only(['company_id', 'branch_id', 'department_id']),
        ]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['invoice_file', 'photos']);

        $asset = DB::transaction(function () use ($data, $request) {
            $asset = Asset::create([...$data, 'created_by' => $request->user()->id]);

            $this->movements->log($asset, MovementType::Alta, comment: 'Alta de activo en el inventario.');

            $this->storeFiles($asset, $request);

            return $asset;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Activo {$asset->internal_code} registrado correctamente."]);

        if ($request->boolean('create_another')) {
            return redirect()->route('assets.create', $request->only(['company_id', 'branch_id', 'department_id']));
        }

        return redirect()->route('assets.show', $asset)->with('justCreated', true);
    }

    public function show(Asset $asset): Response
    {
        $asset->load([
            'company', 'branch', 'department', 'assetType', 'brand',
            'currentResponsible', 'deliveredByResponsible', 'creator:id,name',
            'files.uploader:id,name',
            'movements.user:id,name',
            'reviews.user:id,name',
            'loans.assignedTo', 'loans.deliveredBy', 'loans.receivedBy',
            'parts',
        ]);

        return Inertia::render('activos/Show', [
            'asset' => new AssetResource($asset),
            'statusOptions' => AssetStatus::options(),
            'decommissionReasons' => DecommissionReason::options(),
            'qrUrl' => $this->qrCodeService->publicUrl($asset),
            'justCreated' => (bool) session('justCreated'),
            'actionOptions' => [
                'branches' => Branch::query()->active()->where('company_id', $asset->company_id)->orderBy('name')->get(['id', 'name']),
                'departments' => Department::query()->active()->where(function ($query) use ($asset) {
                    $query->whereNull('company_id')->orWhere('company_id', $asset->company_id);
                })->orderBy('name')->get(['id', 'name']),
                'responsiblePeople' => ResponsiblePerson::query()->active()->where('company_id', $asset->company_id)->orderBy('full_name')->get(['id', 'full_name']),
            ],
        ]);
    }

    public function edit(Asset $asset): Response
    {
        $asset->load(['files']);

        return Inertia::render('activos/Edit', [
            'asset' => $asset,
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $original = $asset->getAttributes();
        $data = $request->safe()->except(['invoice_file', 'photos']);

        DB::transaction(function () use ($asset, $data, $original, $request) {
            $asset->update($data);

            $this->movements->logChanges($asset, $original);

            $this->storeFiles($asset, $request);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Activo actualizado correctamente.']);

        return redirect()->route('assets.show', $asset);
    }

    public function generateCode(Request $request, AssetCodeService $codeService): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'asset_type_id' => ['required', 'integer', 'exists:asset_types,id'],
        ]);

        $company = Company::findOrFail($request->integer('company_id'));
        $assetType = AssetType::findOrFail($request->integer('asset_type_id'));

        return response()->json(['code' => $codeService->generate($company, $assetType)]);
    }

    public function checkSerial(Request $request): JsonResponse
    {
        $request->validate(['serial_number' => ['required', 'string']]);

        $match = Asset::query()
            ->where('serial_number', $request->string('serial_number')->toString())
            ->when($request->integer('except'), fn ($query, $exceptId) => $query->where('id', '!=', $exceptId))
            ->first(['id', 'public_id', 'internal_code', 'name']);

        return response()->json(['match' => $match]);
    }

    private function storeFiles(Asset $asset, Request $request): void
    {
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $path = $file->store('assets/'.$asset->id.'/facturas', 'public');

            $asset->files()->create([
                'type' => AssetFileType::Factura,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()?->id,
            ]);
        }

        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('assets/'.$asset->id.'/fotos', 'public');

            $asset->files()->create([
                'type' => AssetFileType::Foto,
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime' => $photo->getClientMimeType(),
                'size' => $photo->getSize(),
                'uploaded_by' => $request->user()?->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'assetTypes' => AssetType::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']),
            'responsiblePeople' => ResponsiblePerson::query()->active()->orderBy('full_name')->get(['id', 'full_name', 'company_id', 'branch_id', 'department_id']),
            'statuses' => AssetStatus::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name', 'code']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            'assetTypes' => AssetType::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'responsiblePeople' => ResponsiblePerson::query()->orderBy('full_name')->get(['id', 'full_name']),
            'statuses' => AssetStatus::options(),
        ];
    }
}
