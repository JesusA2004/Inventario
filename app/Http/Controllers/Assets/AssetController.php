<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetStatus;
use App\Enums\DecommissionReason;
use App\Enums\MovementType;
use App\Exports\AssetsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\AssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetResponsibility;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Services\AssetCodeService;
use App\Services\AssetFileService;
use App\Services\AssetMovementService;
use App\Services\LabelSizeResolver;
use App\Services\QrCodeService;
use App\Services\Responsibilities\AssetResponsibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AssetController extends Controller implements HasMiddleware
{
    /**
     * Whitelist de columnas ordenables desde /activos?sort=. Nunca se debe
     * pasar $request->sort directo a orderBy(): una columna inexistente
     * (?sort=foo) tronaría con un 500 de SQL en vez de simplemente
     * ignorarse.
     */
    private const array SORTABLE_COLUMNS = [
        'created_at', 'internal_code', 'name', 'serial_number', 'acquired_at', 'last_reviewed_at',
    ];

    /**
     * Tope de una operación masiva (ZIP de QR, materializar IDs para
     * "seleccionar todos los filtrados"). Si el filtro actual arroja más
     * activos que esto, se rechaza con un mensaje claro en vez de
     * truncar en silencio y generar un resultado incompleto sin avisar.
     */
    private const int MAX_BULK_SELECTION = 1000;

    public function __construct(
        private readonly AssetMovementService $movements,
        private readonly QrCodeService $qrCodeService,
        private readonly AssetFileService $files,
        private readonly AssetResponsibilityService $responsibilities,
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
     * Buscador rápido de activos (usado por el selector de Préstamos y el
     * "activo relacionado" de Piezas). Sin texto todavía muestra un listado
     * inicial en vez de quedar vacío, para que el usuario pueda elegir
     * navegando en vez de forzarlo a escribir primero. Busca por clave,
     * nombre, número de serie, modelo, marca y responsable actual.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $assets = Asset::query()
            ->with('latestPhoto')
            ->when($request->boolean('in_inventory_only'), fn ($query) => $query->where('in_inventory', true))
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('currentResponsible', fn ($responsible) => $responsible->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('internal_code')
            ->limit($search ? 20 : 24)
            ->get(['id', 'public_id', 'internal_code', 'name', 'company_id', 'asset_type_id'])
            ->load('assetType:id,name')
            ->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'public_id' => $asset->public_id,
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'company_id' => $asset->company_id,
                'asset_type' => $asset->assetType?->name,
                'photo_url' => $asset->latestPhoto ? Storage::url($asset->latestPhoto->path) : null,
            ]);

        return response()->json($assets);
    }

    /**
     * Devuelve los IDs de los activos que coinciden con los filtros actuales
     * de /activos. Se usa cuando el usuario, estando en modo "todos los N
     * resultados filtrados", deselecciona un activo puntual: ahí hay que
     * materializar la selección abstracta en IDs concretos para poder
     * quitar uno. Si hay más de los que se pueden manejar de forma
     * explícita, se rechaza con un mensaje claro en vez de devolver una
     * lista truncada silenciosamente (como pasaba antes con el límite fijo
     * de 1000 + una bandera "truncated" que el frontend nunca leía).
     */
    public function filteredIds(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request);
        $total = (clone $query)->count();

        abort_if($total > self::MAX_BULK_SELECTION, 422, "Hay {$total} activos que coinciden con estos filtros; el máximo para seleccionar de forma individual es ".self::MAX_BULK_SELECTION.'. Ajusta los filtros para reducir el resultado.');

        return response()->json(['ids' => $query->pluck('id'), 'total' => $total]);
    }

    /**
     * Resuelve la colección de activos sobre la que operará una acción
     * masiva (ZIP de QR, PDF de etiquetas): selección explícita por IDs
     * (checkboxes marcados) o "todos los resultados filtrados" (vuelve a
     * correr filteredQuery() con los filtros actuales en vez de depender de
     * una lista de IDs enviada por el navegador). En ambos casos se valida
     * el total ANTES de traer los registros, para nunca truncar en
     * silencio: si hay demasiados, se rechaza con un mensaje explícito.
     *
     * @return Collection<int, Asset>
     */
    private function resolveBulkSelection(Request $request): Collection
    {
        $request->validate([
            'selection_mode' => ['nullable', 'string', 'in:ids,all_filtered'],
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer'],
        ]);

        $mode = $request->string('selection_mode', $request->filled('asset_ids') ? 'ids' : 'all_filtered')->toString();

        $query = $mode === 'ids'
            ? Asset::query()->whereIn('id', $request->array('asset_ids'))
            : $this->filteredQuery($request);

        $total = (clone $query)->count();

        abort_if($total === 0, 422, 'No hay activos para esta operación.');
        abort_if($total > self::MAX_BULK_SELECTION, 422, "Hay {$total} activos seleccionados; el máximo por operación es ".self::MAX_BULK_SELECTION.'. Ajusta los filtros o selecciona menos activos.');

        return $query->get();
    }

    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'created_at')->toString();
        $sort = in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'created_at';

        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';

        $assets = $this->filteredQuery($request)
            ->with(['company:id,name,code', 'branch:id,name', 'department:id,name', 'brand:id,name', 'assetType:id,name', 'currentResponsible:id,full_name', 'latestPhoto'])
            ->orderBy($sort, $direction)
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
            'labelSizes' => LabelSizeResolver::sharedProps(),
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
        $assets = $this->resolveBulkSelection($request);

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
            'canOverrideGrantor' => (bool) $request->user()?->can('gestionar-responsivas'),
        ]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['invoice_file', 'photos']);

        // Campos aparte de AssetRequest: solo gobiernan si se genera la
        // responsiva de entrega al momento del alta, no el activo en sí, así
        // que no deben colarse en Asset::create().
        $responsibilityOptions = $request->validate([
            'generate_responsibility' => ['boolean'],
            'grantor_name' => ['nullable', 'string', 'max:255'],
            'grantor_position' => ['nullable', 'string', 'max:255'],
            'grantor_email' => ['nullable', 'email', 'max:255'],
        ]);

        $asset = DB::transaction(function () use ($data, $request) {
            $asset = Asset::create([...$data, 'created_by' => $request->user()->id]);

            $this->movements->log($asset, MovementType::Alta, comment: 'Alta de activo en el inventario.');

            $this->storeFiles($asset, $request);

            return $asset;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Activo {$asset->internal_code} registrado correctamente."]);

        // Si ya se capturó un responsable real durante el alta, se ofrece
        // generar de una vez la responsiva de entrega (mismo comportamiento
        // que "Asignar / cambiar responsable" en la ficha), en vez de obligar
        // a repetir el paso justo después de crear el activo. Se hace fuera
        // de la transacción anterior y sin dejar que una responsiva mal
        // configurada (sin plantilla u otorgante) impida guardar el activo.
        if ($asset->current_responsible_id && $request->boolean('generate_responsibility', true)) {
            $overrides = $request->user()->can('gestionar-responsivas')
                ? array_filter([
                    'grantor_name' => $responsibilityOptions['grantor_name'] ?? null,
                    'grantor_position' => $responsibilityOptions['grantor_position'] ?? null,
                    'grantor_email' => $responsibilityOptions['grantor_email'] ?? null,
                ], fn ($value) => filled($value))
                : [];

            try {
                $responsible = ResponsiblePerson::findOrFail($asset->current_responsible_id);
                $responsibility = $this->responsibilities->createDraft($asset->fresh(), $responsible, $overrides, $request->user());

                Inertia::flash('responsibilityCreated', [
                    'public_id' => $responsibility->public_id,
                    'receiver_name' => $responsibility->receiver_name,
                    'has_email' => filled($responsibility->receiver_email),
                ]);
            } catch (ValidationException $e) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'El activo se guardó, pero la responsiva no pudo generarse: '.($e->validator->errors()->first() ?: 'revisa la configuración de documentos.'),
                ]);
            }
        }

        if ($request->boolean('create_another')) {
            return redirect()->route('assets.create', $request->only(['company_id', 'branch_id', 'department_id']));
        }

        return redirect()->route('assets.show', $asset)->with('justCreated', true);
    }

    public function show(Request $request, Asset $asset): Response
    {
        $canSeeResponsibilities = (bool) $request->user()?->can('ver-responsivas');

        $asset->load([
            'company', 'branch', 'department', 'assetType', 'brand',
            'currentResponsible', 'deliveredByResponsible', 'creator:id,name',
            'files.uploader:id,name', 'files.asset:id,public_id',
            'latestPhoto',
            'movements.user:id,name',
            'reviews.user:id,name',
            'loans.assignedTo', 'loans.deliveredBy', 'loans.receivedBy',
            'parts.brand',
        ]);

        if ($canSeeResponsibilities) {
            $asset->load('responsibilities');
        }

        $documentSettings = $asset->company ? CompanyDocumentSetting::forCompany($asset->company) : null;

        // Si el responsable actual todavía no tiene ninguna responsiva
        // (p. ej. se asignó antes de que existiera este módulo), la ficha
        // ofrece un botón para generarla manualmente en vez de obligar a
        // reasignar el responsable solo para disparar el flujo. El botón
        // sigue disponible aunque ya exista una (para volver a emitirla con
        // datos corregidos o dejar constancia de un motivo), solo cambia su
        // etiqueta según este flag.
        $needsResponsibilityForCurrentResponsible = $canSeeResponsibilities
            && $asset->current_responsible_id
            && ! $asset->responsibilities->contains(
                fn (AssetResponsibility $r) => $r->responsible_person_id === $asset->current_responsible_id,
            );
        $canGenerateResponsibility = (bool) $asset->current_responsible_id
            && (bool) $request->user()?->can('crear-responsivas');

        return Inertia::render('activos/Show', [
            // ->resolve() es obligatorio aquí: Inertia trata un JsonResource
            // "crudo" como Responsable y lo envuelve en {"data": {...}} (igual
            // que una respuesta JSON normal de Laravel), dejando todas las
            // props del activo anidadas bajo asset.data.* en vez de asset.*
            // directamente, que es lo que Show.vue espera.
            'asset' => (new AssetResource($asset))->resolve(),
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
                'documentSettings' => [
                    'default_grantor_name' => $documentSettings->default_grantor_name,
                    'default_grantor_position' => $documentSettings->default_grantor_position,
                    'auto_generate_on_assignment' => $documentSettings->auto_generate_on_assignment,
                ],
                'canOverrideGrantor' => (bool) $request->user()?->can('gestionar-responsivas'),
                'needsResponsibilityForCurrentResponsible' => $needsResponsibilityForCurrentResponsible,
                'canGenerateResponsibility' => $canGenerateResponsibility,
            ],
            'labelSizes' => LabelSizeResolver::sharedProps(),
        ]);
    }

    public function edit(Asset $asset): Response
    {
        $asset->load(['files.asset:id,public_id']);

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
            $this->files->storeInvoice($asset, $request->file('invoice_file'), $request->user()?->id);
        }

        foreach ($request->file('photos', []) as $photo) {
            $this->files->storePhoto($asset, $photo, $request->user()?->id);
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
            'documentSettingsByCompany' => $this->documentSettingsByCompany(),
        ];
    }

    /**
     * Config de responsivas (otorgante por defecto, generación automática)
     * de cada empresa activa, indexada por company_id: el formulario de alta
     * elige la empresa dinámicamente, así que no se puede resolver una sola
     * config de antemano como sí ocurre en la ficha de un activo existente.
     *
     * @return array<int, array{default_grantor_name: ?string, default_grantor_position: ?string, auto_generate_on_assignment: bool}>
     */
    private function documentSettingsByCompany(): array
    {
        return Company::query()->active()->get(['id', 'name', 'legal_name'])
            ->mapWithKeys(function (Company $company) {
                $settings = CompanyDocumentSetting::forCompany($company);

                return [$company->id => [
                    'default_grantor_name' => $settings->default_grantor_name,
                    'default_grantor_position' => $settings->default_grantor_position,
                    'auto_generate_on_assignment' => $settings->auto_generate_on_assignment,
                ]];
            })
            ->all();
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
