<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "acquired_at" era NOT NULL, pero varios activos reales no tienen una
     * fecha de adquisición conocida (no la inventamos).
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('acquired_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('acquired_at')->nullable(false)->change();
        });
    }
};
