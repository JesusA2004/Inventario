<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('internal_code', 50)->unique();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status', 30)->default('activo');
            $table->boolean('in_inventory')->default(true);
            $table->foreignId('current_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->foreignId('delivered_by_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->text('components')->nullable();
            $table->text('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->string('invoice_url')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('acquired_at');
            $table->date('decommissioned_at')->nullable();
            $table->string('decommission_reason', 50)->nullable();
            $table->text('decommission_notes')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'department_id']);
            $table->index('status');
            $table->index('in_inventory');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
