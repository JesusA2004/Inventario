<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('document_number')->nullable()->unique();

            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('responsible_person_id')->constrained('responsible_people')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 30)->default('borrador');

            // Snapshot del receptor/otorgante en el momento de la emisión.
            $table->string('receiver_name');
            $table->string('receiver_position')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('grantor_name');
            $table->string('grantor_position')->nullable();
            $table->string('grantor_email')->nullable();

            $table->date('issued_at');
            $table->string('city')->nullable();

            // Documento histórico: nunca se recalcula contra el estado
            // actual de activo/responsable/empresa/plantilla.
            $table->json('asset_snapshot');
            $table->longText('legal_text_snapshot');
            $table->unsignedInteger('legal_template_version');
            $table->unsignedInteger('privacy_notice_version');

            $table->string('unsigned_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('physical_copy_path')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('physical_received_at')->nullable();
            $table->foreignId('physical_received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('signature_path')->nullable();
            $table->string('signature_sha256', 64)->nullable();
            $table->string('document_sha256', 64)->nullable();

            $table->string('signer_ip', 45)->nullable();
            $table->string('signer_user_agent')->nullable();

            // Solo el hash del token vive aquí; el valor en claro solo
            // existe en el correo enviado al firmante.
            $table->string('signing_token_hash', 64)->nullable();
            $table->timestamp('signing_token_expires_at')->nullable();

            $table->timestamp('consent_accepted_at')->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();

            $table->timestamps();

            $table->index('asset_id');
            $table->index('responsible_person_id');
            $table->index('status');
            $table->index('signing_token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_responsibilities');
    }
};
