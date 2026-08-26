<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsibility_templates', function (Blueprint $table) {
            $table->id();
            // null = plantilla global (fallback para cualquier empresa que no
            // tenga una propia configurada en company_document_settings).
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title');
            $table->unsignedInteger('version');
            $table->longText('body');
            $table->boolean('active')->default(true);
            // Ninguna responsiva puede enviarse a firma digital mientras la
            // plantilla usada no esté "approved": ver ResponsibilityTemplate::canBeSentForSignature().
            $table->string('legal_review_status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'name', 'version']);
            $table->index(['company_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsibility_templates');
    }
};
