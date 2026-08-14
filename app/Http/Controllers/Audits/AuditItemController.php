<?php

namespace App\Http\Controllers\Audits;

use App\Enums\AuditItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Audit;
use App\Models\AuditItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class AuditItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gestionar-auditorias'),
        ];
    }

    /**
     * Mark (or create, if the asset wasn't part of the original scope) an
     * audit item's outcome. Used both by the manual list in the audit page
     * and by the mobile QR scanner flow.
     */
    public function mark(Request $request, Audit $audit): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'asset_public_id' => ['required', 'string', 'exists:assets,public_id'],
            'status' => ['required', new Enum(AuditItemStatus::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $asset = Asset::where('public_id', $data['asset_public_id'])->firstOrFail();

        $item = AuditItem::firstOrNew(['audit_id' => $audit->id, 'asset_id' => $asset->id]);

        $item->status = $data['status'];
        $item->comment = $data['comment'] ?? null;
        $item->checked_at = now();
        $item->checked_by = $request->user()->id;

        if ($data['status'] === AuditItemStatus::Encontrado->value) {
            $item->found_branch_id = $asset->branch_id;
            $item->found_department_id = $asset->department_id;
            $item->found_responsible_id = $asset->current_responsible_id;
        }

        $item->save();

        if ($request->wantsJson()) {
            return response()->json([
                'item' => [
                    'id' => $item->id,
                    'status' => ['value' => $item->status->value, 'label' => $item->status->label(), 'color' => $item->status->color()],
                ],
                'asset' => ['internal_code' => $asset->internal_code, 'name' => $asset->name],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Se registró {$asset->internal_code}."]);

        return back();
    }
}
