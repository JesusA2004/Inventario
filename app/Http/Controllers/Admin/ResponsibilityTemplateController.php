<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LegalReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ResponsibilityTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

/**
 * Plantillas de clausulado legal, versionadas. Nueva versión = nuevo
 * registro, nunca se sobrescribe una versión ya usada por una responsiva
 * (esas la conservan congelada en legal_text_snapshot). Toda plantilla
 * nace en "draft" y requiere aprobación explícita antes de poder usarse
 * para enviar una responsiva a firma digital.
 */
class ResponsibilityTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:configurar-responsivas'),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'document_title' => ['required', 'string', 'max:255'],
            'recipient_block' => ['nullable', 'string', 'max:2000'],
            'receiver_label' => ['nullable', 'string', 'max:60'],
            'grantor_label' => ['nullable', 'string', 'max:60'],
            'body' => ['required', 'string'],
        ]);

        $companyId = $data['company_id'] ?? null;

        $query = ResponsibilityTemplate::query()->where('name', $data['name']);
        $query = $companyId ? $query->where('company_id', $companyId) : $query->whereNull('company_id');
        $nextVersion = ((int) $query->max('version')) + 1;

        $template = ResponsibilityTemplate::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'title' => $data['title'],
            'document_title' => $data['document_title'],
            'recipient_block' => $data['recipient_block'] ?? null,
            'receiver_label' => $data['receiver_label'] ?? null,
            'grantor_label' => $data['grantor_label'] ?? null,
            'version' => $nextVersion,
            'body' => $data['body'],
            'active' => true,
            'legal_review_status' => LegalReviewStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Plantilla creada como versión {$template->version} — requiere validación jurídica antes de poder usarse para firma digital.",
        ]);

        return back();
    }

    public function approve(ResponsibilityTemplate $template): RedirectResponse
    {
        $template->update(['legal_review_status' => LegalReviewStatus::Approved]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla marcada como aprobada. Ya puede usarse para enviar responsivas a firma digital.']);

        return back();
    }

    public function deactivate(ResponsibilityTemplate $template): RedirectResponse
    {
        $template->update(['active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla desactivada.']);

        return back();
    }
}
