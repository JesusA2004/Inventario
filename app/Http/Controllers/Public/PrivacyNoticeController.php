<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PrivacyNoticeVersion;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyNoticeController extends Controller
{
    public function show(): Response
    {
        $notice = PrivacyNoticeVersion::current();

        return Inertia::render('public/PrivacyNotice', [
            'version' => $notice?->version,
            'body' => $notice?->body,
        ]);
    }
}
