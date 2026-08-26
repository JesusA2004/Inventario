<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\PrivacyNoticeVersion;
use App\Models\ResponsibilityTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración → Documentos / Responsivas. Por empresa: nunca se
 * hardcodea el otorgante, la razón social usada en responsivas ni el
 * membrete. Cambiar esta configuración NUNCA modifica responsivas ya
 * emitidas (viven de su propio snapshot).
 */
class ResponsibilitySettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:configurar-responsivas'),
        ];
    }

    public function edit(Request $request): Response
    {
        $companies = Company::query()->active()->orderBy('name')->get(['id', 'name']);
        $companyId = $request->integer('company_id') ?: $companies->first()?->id;
        $company = $companyId ? Company::findOrFail($companyId) : null;

        $settings = $company ? CompanyDocumentSetting::forCompany($company) : null;

        return Inertia::render('configuracion/Responsivas', [
            'companies' => $companies,
            'selectedCompanyId' => $companyId,
            'settings' => $settings,
            'templates' => $company
                ? ResponsibilityTemplate::query()
                    ->where(fn ($q) => $q->where('company_id', $company->id)->orWhereNull('company_id'))
                    ->orderByDesc('version')->get()
                : collect(),
            'privacyNotices' => PrivacyNoticeVersion::query()->orderByDesc('version')->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'issuing_city' => ['nullable', 'string', 'max:255'],
            'institutional_receiver_name' => ['nullable', 'string', 'max:255'],
            'institutional_receiver_position' => ['nullable', 'string', 'max:255'],
            'default_grantor_name' => ['required', 'string', 'max:255'],
            'default_grantor_position' => ['nullable', 'string', 'max:255'],
            'default_grantor_email' => ['nullable', 'email', 'max:255'],
            'active_responsibility_template_id' => ['nullable', 'integer', 'exists:responsibility_templates,id'],
            'privacy_contact_email' => ['nullable', 'email', 'max:255'],
            'privacy_notice_url' => ['nullable', 'url', 'max:255'],
            'signing_link_valid_days' => ['required', 'integer', 'min:1', 'max:90'],
            'auto_generate_on_assignment' => ['boolean'],
            'letterhead' => ['nullable', 'image', 'max:8192'],
            'header_logo' => ['nullable', 'image', 'max:4096'],
            'watermark' => ['nullable', 'image', 'max:4096'],
            'footer' => ['nullable', 'image', 'max:4096'],
            'remove_letterhead' => ['boolean'],
            'remove_header_logo' => ['boolean'],
            'remove_watermark' => ['boolean'],
            'remove_footer' => ['boolean'],
        ]);

        $settings = CompanyDocumentSetting::forCompany($company);

        // Membrete de página completa (logo + fondo + franja ya
        // compuestos) o piezas sueltas (logo/watermark/franja): el mismo
        // patrón para las cuatro imágenes de branding, cada una remplazable
        // o eliminable de forma independiente.
        foreach ([
            'letterhead' => 'letterhead_path',
            'header_logo' => 'header_logo_path',
            'watermark' => 'watermark_path',
            'footer' => 'footer_path',
        ] as $inputName => $column) {
            if ($request->hasFile($inputName)) {
                if ($settings->{$column}) {
                    Storage::disk('public')->delete($settings->{$column});
                }
                $data[$column] = $request->file($inputName)->store('branding/responsivas/'.$company->id, 'public');
            } elseif ($request->boolean('remove_'.$inputName) && $settings->{$column}) {
                Storage::disk('public')->delete($settings->{$column});
                $data[$column] = null;
            }
        }

        unset($data['letterhead'], $data['header_logo'], $data['watermark'], $data['footer']);
        unset($data['remove_letterhead'], $data['remove_header_logo'], $data['remove_watermark'], $data['remove_footer']);

        $settings->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración de responsivas actualizada.']);

        return back();
    }
}
