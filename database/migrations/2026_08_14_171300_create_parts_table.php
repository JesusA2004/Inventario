<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('internal_code', 50)->unique();
            $table->foreignId('related_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('name');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('part_number')->nullable();
            $table->string('status', 30)->default('funcional');
            $table->boolean('in_inventory')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->text('specifications')->nullable();
            $table->boolean('assembled')->default(false);
            $table->text('notes')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('decommissioned_at')->nullable();
            $table->string('decommission_reason', 50)->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->string('invoice_url')->nullable();
            $table->boolean('needs_label')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
