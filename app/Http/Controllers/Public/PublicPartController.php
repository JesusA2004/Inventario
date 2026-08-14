<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Inertia\Inertia;
use Inertia\Response;

class PublicPartController extends Controller
{
    public function show(Part $part): Response
    {
        $part->load(['company', 'branch', 'brand', 'relatedAsset']);

        return Inertia::render('public/PartShow', [
            'part' => [
                'internal_code' => $part->internal_code,
                'name' => $part->name,
                'part_number' => $part->part_number,
                'serial_number' => $part->serial_number,
                'status' => ['label' => $part->status->label(), 'color' => $part->status->color()],
                'in_inventory' => (bool) $part->in_inventory,
                'assembled' => (bool) $part->assembled,
                'company_name' => $part->company?->name,
                'branch_name' => $part->branch?->name,
                'brand_name' => $part->brand?->name,
                'related_asset_code' => $part->relatedAsset?->internal_code,
            ],
        ]);
    }
}
