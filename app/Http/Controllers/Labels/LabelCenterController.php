<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Services\AssetLabelPdfService;
use App\Services\LabelSizeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class LabelCenterController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetLabelPdfService $labelPdfService,
        private readonly LabelSizeResolver $sizeResolver,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-activos'),
        ];
    }

    public function index(Request $request): Response
    {
        $assets = $this->filteredQuery($request)
            ->with(['company:id,name,code', 'branch:id,name', 'department:id,name', 'assetType:id,name'])
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Asset $asset) => (new AssetResource($asset))->resolve());

        return Inertia::render('etiquetas/Index', [
            'assets' => $assets,
            'filters' => $request->only(['q', 'company_id', 'branch_id', 'department_id', 'recent']),
            'filterOptions' => [
                'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
                'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'company_id']),
                'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'company_id']),
            ],
            'labelSizes' => LabelSizeResolver::sharedProps(),
        ]);
    }

    public function pdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer'],
            'template' => ['nullable', 'string', 'in:standard,compact'],
            'size' => ['nullable', 'string', 'in:small,medium,large,custom'],
            'width_mm' => ['required_if:size,custom', 'nullable', 'numeric', 'min:'.LabelSizeResolver::MIN_WIDTH_MM, 'max:'.LabelSizeResolver::MAX_WIDTH_MM],
            'height_mm' => ['required_if:size,custom', 'nullable', 'numeric', 'min:'.LabelSizeResolver::MIN_HEIGHT_MM, 'max:'.LabelSizeResolver::MAX_HEIGHT_MM],
        ]);

        $query = $request->filled('asset_ids')
            ? Asset::query()->whereIn('id', $request->array('asset_ids'))
            : $this->filteredQuery($request)->limit(500);

        $assets = $query->with('assetType', 'company')->get();

        abort_if($assets->isEmpty(), 422, 'Selecciona al menos un activo para generar las etiquetas.');

        $template = $request->string('template', 'standard')->toString();
        $size = $data['size'] ?? 'medium';
        $columns = $template === 'compact' ? 3 : 2;

        // Defensa en el servidor: aunque el diálogo ya evita elegir
        // combinaciones incompatibles, se vuelve a validar aquí para que un
        // POST manual no pueda generar etiquetas cortadas o con el QR
        // ilegible.
        $width = $size === 'custom' ? (float) $data['width_mm'] : LabelSizeResolver::PRESETS[$size]['width'];
        abort_unless(
            $this->sizeResolver->fitsColumns($width, $columns),
            422,
            "El tamaño seleccionado no cabe en {$columns} columnas sin cortarse. Máximo ".$this->sizeResolver->maxWidthForColumns($columns).'mm de ancho.',
        );

        return $this->labelPdfService
            ->build($assets, $template, $size, isset($data['width_mm']) ? (float) $data['width_mm'] : null, isset($data['height_mm']) ? (float) $data['height_mm'] : null)
            ->stream('etiquetas-qr-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filteredQuery(Request $request): Builder
    {
        return Asset::query()
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->when($request->integer('branch_id'), fn ($query, $value) => $query->where('branch_id', $value))
            ->when($request->integer('department_id'), fn ($query, $value) => $query->where('department_id', $value))
            ->when($request->boolean('recent'), fn ($query) => $query->where('created_at', '>=', now()->subDays(7)));
    }
}
