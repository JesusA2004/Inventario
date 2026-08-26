<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_document_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('legal_business_name')->nullable();
            $table->string('issuing_city')->nullable();
            $table->string('institutional_receiver_name')->nullable();
            $table->string('institutional_receiver_position')->nullable();

            // El otorgante actual (hoy: Jesus Arizmendi Maya) es solo el
            // valor por defecto configurado aquí; nunca se hardcodea en
            // código ni en las vistas Blade.
            $table->string('default_grantor_name')->nullable();
            $table->string('default_grantor_position')->nullable();
            $table->string('default_grantor_email')->nullable();

            $table->string('letterhead_path')->nullable();
            // Nombre de constraint explícito y corto: el autogenerado
            // ("company_document_settings_active_responsibility_template_id_foreign")
            // excede el límite de 64 caracteres de MySQL para identificadores.
            $table->foreignId('active_responsibility_template_id')->nullable()
                ->constrained('responsibility_templates')
                ->name('cds_active_template_fk')
                ->nullOnDelete();

            $table->string('privacy_contact_email')->nullable();
            $table->string('privacy_notice_url')->nullable();

            $table->unsignedSmallInteger('signing_link_valid_days')->default(7);
            $table->boolean('auto_generate_on_assignment')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_document_settings');
    }
};
