<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyNoticeVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

/**
 * Versiona el aviso de privacidad mostrado en /aviso-de-privacidad y en la
 * página pública de firma. Cada responsiva congela el número de versión
 * vigente al momento de su creación (privacy_notice_version): publicar una
 * versión nueva nunca reescribe lo que ya se le mostró a alguien que firmó
 * con una versión anterior.
 */
class PrivacyNoticeController extends Controller implements HasMiddleware
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
            'body' => ['required', 'string'],
        ]);

        $nextVersion = ((int) PrivacyNoticeVersion::query()->max('version')) + 1;

        PrivacyNoticeVersion::query()->update(['active' => false]);

        PrivacyNoticeVersion::create([
            'version' => $nextVersion,
            'body' => $data['body'],
            'active' => true,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Aviso de privacidad publicado como versión {$nextVersion}."]);

        return back();
    }
}
