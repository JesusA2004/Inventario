<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('system_name')->default('Inventario TI');
            $table->string('logo_path')->nullable();
            $table->string('qr_base_url')->nullable();
            $table->string('internal_code_format')->default('{empresa}-{tipo}-{consecutivo}');
            $table->string('label_template', 20)->default('standard');
            $table->foreignId('default_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
