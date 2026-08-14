<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('pendiente');
            $table->foreignId('found_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('found_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('found_responsible_id')->nullable()->constrained('responsible_people')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['audit_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_items');
    }
};
