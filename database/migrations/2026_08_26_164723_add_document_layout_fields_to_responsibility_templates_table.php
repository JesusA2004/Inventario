<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsibility_templates', function (Blueprint $table) {
            // Título exacto del documento ("RESPONSIVA DE EQUIPO DE
            // CÓMPUTO"): antes se adivinaba a partir del tipo de activo
            // (App\Enums\ResponsibilityProfile), lo que producía títulos
            // distintos al formato real de la empresa. Ahora vive en la
            // plantilla, tal cual el formato lo dicta.
            $table->string('document_title')->nullable()->after('title');

            // Bloque de destinatario tal cual aparece en el documento real
            // ("A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE
            // C.V.\nPRESENTE."). Admite los mismos placeholders {{ }} que
            // el cuerpo. Antes de esto, el PDF generaba su propio párrafo de
            // "Destinatario / Empresa" inventado, distinto al formato real.
            $table->text('recipient_block')->nullable()->after('document_title');

            // Etiquetas de la sección de firmas ("Receptor" / "Otorgante"
            // en el formato real de MR LANA); nulo = se usa ese mismo valor
            // por defecto en código, sin forzar a cada empresa a capturarlo.
            $table->string('receiver_label')->nullable()->after('recipient_block');
            $table->string('grantor_label')->nullable()->after('receiver_label');
        });
    }

    public function down(): void
    {
        Schema::table('responsibility_templates', function (Blueprint $table) {
            $table->dropColumn(['document_title', 'recipient_block', 'receiver_label', 'grantor_label']);
        });
    }
};
