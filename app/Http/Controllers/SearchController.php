<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ResponsiblePerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Powers the global Ctrl+K search palette: matches assets by internal
     * code, name, serial number, model, brand, or responsible.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());

        if (mb_strlen($search) < 2) {
            return response()->json(['assets' => [], 'responsiblePeople' => []]);
        }

        $assets = Asset::query()
            ->with(['company:id,name', 'branch:id,name', 'brand:id,name'])
            ->where(function ($query) use ($search) {
                $query->where('internal_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($inner) => $inner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('currentResponsible', fn ($inner) => $inner->where('full_name', 'like', "%{$search}%"));
            })
            ->limit(8)
            ->get(['id', 'public_id', 'internal_code', 'name', 'company_id', 'branch_id', 'brand_id'])
            ->map(fn (Asset $asset) => [
                'public_id' => $asset->public_id,
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'subtitle' => collect([$asset->company?->name, $asset->branch?->name])->filter()->implode(' · '),
            ]);

        $responsiblePeople = ResponsiblePerson::query()
            ->where('full_name', 'like', "%{$search}%")
            ->limit(5)
            ->get(['id', 'full_name'])
            ->map(fn (ResponsiblePerson $person) => ['id' => $person->id, 'full_name' => $person->full_name]);

        return response()->json([
            'assets' => $assets,
            'responsiblePeople' => $responsiblePeople,
        ]);
    }
}
