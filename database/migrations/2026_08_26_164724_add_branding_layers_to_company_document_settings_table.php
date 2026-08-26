<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_document_settings', function (Blueprint $table) {
            // letterhead_path ya existente = membrete de página completa
            // (logo + fondo + franja ya compuestos en una sola imagen, como
            // el oficial de MR LANA). Estas tres son la alternativa para una
            // empresa que prefiera capas sueltas en vez de un membrete
            // pre-compuesto: si letterhead_path existe, tiene prioridad y
            // estas se ignoran.
            $table->string('header_logo_path')->nullable()->after('letterhead_path');
            $table->string('watermark_path')->nullable()->after('header_logo_path');
            $table->string('footer_path')->nullable()->after('watermark_path');
        });
    }

    public function down(): void
    {
        Schema::table('company_document_settings', function (Blueprint $table) {
            $table->dropColumn(['header_logo_path', 'watermark_path', 'footer_path']);
        });
    }
};
