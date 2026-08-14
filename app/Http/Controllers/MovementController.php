<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Resources\AssetMovementResource;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class MovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-activos'),
        ];
    }

    /**
     * Historial global de movimientos: todos los activos, no uno a la vez.
     */
    public function index(Request $request): Response
    {
        $movements = AssetMovement::query()
            ->with(['asset:id,public_id,internal_code,name,company_id,branch_id', 'asset.company:id,name', 'asset.branch:id,name', 'user:id,name'])
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->whereHas('asset', function ($inner) use ($search) {
                    $inner->where('internal_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('company_id'), function ($query, $companyId) {
                $query->whereHas('asset', fn ($asset) => $asset->where('company_id', $companyId));
            })
            ->when($request->integer('branch_id'), function ($query, $branchId) {
                $query->whereHas('asset', fn ($asset) => $asset->where('branch_id', $branchId));
            })
            ->when($request->integer('asset_id'), fn ($query, $assetId) => $query->where('asset_id', $assetId))
            ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($request->string('type')->toString(), fn ($query, $type) => $query->where('type', $type))
            ->when($request->date('from'), fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($request->date('to'), fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AssetMovement $movement) => (new AssetMovementResource($movement))->resolve());

        return Inertia::render('movimientos/Index', [
            'movements' => $movements,
            'filters' => $request->only(['q', 'company_id', 'branch_id', 'user_id', 'type', 'from', 'to']),
            'filterOptions' => [
                'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
                'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'company_id']),
                'users' => User::query()->orderBy('name')->get(['id', 'name']),
                'types' => collect(MovementType::cases())->map(fn (MovementType $type) => ['value' => $type->value, 'label' => $type->label()])->values(),
            ],
        ]);
    }
}
