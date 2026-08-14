<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gestionar-configuracion'),
        ];
    }

    public function edit(): Response
    {
        return Inertia::render('configuracion/Index', [
            'settings' => SystemSetting::current(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'system_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'qr_base_url' => ['nullable', 'url', 'max:255'],
            'internal_code_format' => ['required', 'string', 'max:255'],
            'label_template' => ['required', 'string', 'in:standard,compact'],
            'default_company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $settings = SystemSetting::current();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('settings', 'public');
        }

        unset($data['logo']);

        $settings->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración actualizada correctamente.']);

        return back();
    }
}
