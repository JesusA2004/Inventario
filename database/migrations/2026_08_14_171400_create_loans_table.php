<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->foreignId('delivered_by_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->foreignId('received_by_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->date('loan_date');
            $table->date('expected_return_date')->nullable();
            $table->boolean('delivered_confirmed')->default(false);
            $table->boolean('received_confirmed')->default(false);
            $table->date('actual_return_date')->nullable();
            $table->string('status', 20)->default('prestado');
            $table->text('return_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expected_return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
