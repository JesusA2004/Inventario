<?php

namespace App\Http\Controllers\Public;

use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use Inertia\Inertia;
use Inertia\Response;

class PublicAssetController extends Controller
{
    /**
     * Mobile-first public ficha shown when the permanent QR code is
     * scanned. No login required, but only non-sensitive fields are
     * exposed (no observaciones, no factura, no historial).
     */
    public function show(Asset $asset): Response
    {
        $asset->load(['company', 'branch', 'department', 'assetType', 'brand', 'currentResponsible']);

        $status = $asset->status instanceof AssetStatus ? $asset->status : AssetStatus::tryFrom((string) $asset->status);

        return Inertia::render('public/AssetShow', [
            'asset' => [
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'status' => $status ? ['label' => $status->label(), 'color' => $status->color()] : null,
                'in_inventory' => (bool) $asset->in_inventory,
                'company_name' => $asset->company?->name,
                'branch_name' => $asset->branch?->name,
                'department_name' => $asset->department?->name,
                'asset_type_name' => $asset->assetType?->name,
                'brand_name' => $asset->brand?->name,
                'responsible_name' => $asset->currentResponsible?->full_name,
                'last_reviewed_at' => $asset->last_reviewed_at?->toDateString(),
            ],
        ]);
    }
}
