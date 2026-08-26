<?php

namespace App\Http\Controllers\Catalogs;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\ResponsiblePersonRequest;
use App\Http\Resources\AssetResponsibilityResource;
use App\Models\AssetResponsibility;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Models\User;
use App\Services\AssetMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ResponsiblePersonController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetMovementService $movements,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-catalogos', only: ['index', 'show']),
            new Middleware('permission:gestionar-catalogos', except: ['index', 'show']),
        ];
    }

    /**
     * Ficha del responsable: equipos actuales a su cargo + historial de
     * responsivas (incluye las de equipos que ya no están a su cargo, para
     * conservar el historial completo aunque el activo cambie de dueño).
     */
    public function show(ResponsiblePerson $responsable): Response
    {
        $responsable->load(['company:id,name', 'branch:id,name', 'department:id,name']);

        $assets = $responsable->assetsInCharge()
            ->with(['assetType:id,name', 'brand:id,name'])
            ->get(['id', 'public_id', 'internal_code', 'name', 'asset_type_id', 'brand_id', 'status'])
            ->map(fn ($asset) => [
                'public_id' => $asset->public_id,
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'asset_type' => $asset->assetType?->name,
                'brand' => $asset->brand?->name,
                'status' => ['value' => $asset->status->value, 'label' => $asset->status->label(), 'color' => $asset->status->color()],
            ]);

        $responsibilities = AssetResponsibility::query()
            ->where('responsible_person_id', $responsable->id)
            ->with('asset:id,public_id,internal_code,name')
            ->latest('issued_at')
            ->get();

        return Inertia::render('responsables/Show', [
            'responsible' => $responsable,
            'assets' => $assets,
            'responsibilities' => AssetResponsibilityResource::collection($responsibilities)->resolve(),
        ]);
    }

    public function index(Request $request): Response
    {
        $responsiblePeople = ResponsiblePerson::query()
            ->with(['company:id,name,code', 'branch:id,name', 'department:id,name'])
            ->withCount('assetsInCharge')
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->orderBy('full_name')
            ->get();

        return Inertia::render('responsables/Index', [
            'responsiblePeople' => $responsiblePeople,
            'companies' => Company::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name', 'company_id']),
            'filters' => $request->only('q', 'company_id'),
        ]);
    }

    /**
     * Used by the "+ Nuevo responsable" quick-create dialog embedded in the
     * asset form, so it must respond with JSON instead of redirecting.
     */
    public function store(ResponsiblePersonRequest $request): RedirectResponse|JsonResponse
    {
        $responsible = ResponsiblePerson::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($responsible);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable creado correctamente.']);

        return back();
    }

    public function update(ResponsiblePersonRequest $request, ResponsiblePerson $responsable): RedirectResponse
    {
        $wasActive = $responsable->active;
        $data = $request->validated();

        $responsable->update($data);

        // Dar de baja a un responsable no puede dejar equipo "flotando" a su
        // nombre: sus activos vuelven a quedar sin responsable (a cargo de la
        // empresa) y quien hizo la baja queda registrado como quien los
        // recibió. No hace falta reasignarlos uno por uno ni recrear nada:
        // el botón "Asignar" en cada activo se encarga de la reasignación.
        // Se compara contra el valor validado en vez de $responsable->active
        // (ya mutado por el update de arriba) para decidir si en verdad hubo
        // una transición de activo a inactivo.
        $message = 'Responsable actualizado correctamente.';
        $isNowActive = array_key_exists('active', $data) ? (bool) $data['active'] : $wasActive;

        if ($wasActive && ! $isNowActive) {
            $returned = $this->returnAssetsToCompany($responsable, $request->user());

            if ($returned > 0) {
                $message .= " Se devolvieron {$returned} activo(s) a la empresa.";
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * Libera todos los activos a cargo del responsable (vuelven a quedar sin
     * responsable asignado) y deja constancia en el historial de cada uno de
     * quién los recibió.
     */
    private function returnAssetsToCompany(ResponsiblePerson $responsable, User $receivedBy): int
    {
        $assets = $responsable->assetsInCharge()->get();

        foreach ($assets as $asset) {
            $asset->update(['current_responsible_id' => null]);

            $this->movements->log(
                $asset,
                MovementType::CambioResponsable,
                field: 'current_responsible_id',
                oldValue: $responsable->full_name,
                newValue: 'Sin asignar',
                comment: "Devuelto a la empresa: {$responsable->full_name} fue dado de baja como responsable. Recibió: {$receivedBy->name}.",
            );
        }

        return $assets->count();
    }

    public function destroy(ResponsiblePerson $responsable): RedirectResponse
    {
        if ($responsable->assetsInCharge()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: tiene activos asignados. Desactívalo en su lugar.',
            ]);

            return back();
        }

        $responsable->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable eliminado.']);

        return back();
    }
}
