<?php

use App\Models\Company;
use App\Models\ResponsibilityTemplate;
use Database\Seeders\CompaniesSeeder;
use Database\Seeders\ResponsibilityTemplateSeeder;

/**
 * Blinda el texto EXACTO que el cliente entregó como formato real de MR
 * LANA: si algún cambio futuro vuelve a parafrasear o "corregir" el
 * clausulado (como pasó antes con la cita al artículo 32), esta prueba debe
 * reventar. Nunca actualices estos strings sin que el cliente confirme que
 * cambió el documento real.
 */
test('the MR LANA template is seeded with the exact real document text', function () {
    $this->seed(CompaniesSeeder::class);
    $this->seed(ResponsibilityTemplateSeeder::class);

    $mrLana = Company::where('code', 'CML')->firstOrFail();
    $template = ResponsibilityTemplate::where('company_id', $mrLana->id)->where('name', 'default')->firstOrFail();

    expect($template->document_title)->toBe('RESPONSIVA DE EQUIPO DE CÓMPUTO');
    expect($template->recipient_block)->toBe(
        "A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.\nPRESENTE.",
    );

    // La cita real es al artículo 32, fracción III -no al 132-: es
    // precisamente el "arreglo" indebido que motivó reescribir este módulo.
    expect($template->body)->toContain('artículo 32, fracción III de la Ley Federal del Trabajo');
    expect($template->body)->not->toContain('artículo 132');
    expect($template->body)->toContain('artículo 134, fracción VI de la Ley Federal del Trabajo');
    expect($template->body)->toContain(ResponsibilityTemplate::CHARACTERISTICS_TABLE_TOKEN);
    expect($template->legal_review_status->value)->toBe('draft');
});

test('the global fallback template generalizes the company name via placeholder instead of a hardcoded one', function () {
    $this->seed(ResponsibilityTemplateSeeder::class);

    $global = ResponsibilityTemplate::whereNull('company_id')->where('name', 'default')->firstOrFail();

    expect($global->recipient_block)->toBe("A QUIEN CORRESPONDA\n{{ company_name }}\nPRESENTE.");
    expect($global->body)->not->toContain('artículo 132');
});
