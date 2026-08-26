<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            // Determina el título/tipo de documento en la responsiva de
            // equipo (RESPONSIVA DE EQUIPO DE CÓMPUTO vs. DISPOSITIVO MÓVIL,
            // etc.) sin depender de strings mágicos sobre el nombre del tipo.
            $table->string('responsibility_profile', 20)->default('general')->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn('responsibility_profile');
        });
    }
};
