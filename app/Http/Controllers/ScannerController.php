<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Models\Asset;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScannerController extends Controller
{
    public function index(Request $request): Response
    {
        $audit = null;

        if ($request->filled('audit_id')) {
            $audit = Audit::query()->find($request->integer('audit_id'), ['id', 'name']);
        }

        return Inertia::render('escanear/Index', [
            'audit' => $audit,
            'activeAudits' => Audit::query()
                ->where('status', AuditStatus::EnProgreso)
                ->orderByDesc('started_at')
                ->get(['id', 'name']),
        ]);
    }

    public function lookup(string $publicId): JsonResponse
    {
        $asset = Asset::query()
            ->where('public_id', $publicId)
            ->with(['branch:id,name', 'department:id,name', 'currentResponsible:id,full_name', 'assetType:id,name'])
            ->first();

        if (! $asset) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'asset' => [
                'public_id' => $asset->public_id,
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'type' => $asset->assetType?->name,
                'branch' => $asset->branch?->name,
                'department' => $asset->department?->name,
                'responsible' => $asset->currentResponsible?->full_name,
            ],
        ]);
    }
}
