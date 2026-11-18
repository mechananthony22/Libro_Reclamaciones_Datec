<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->text('datos_consumidor')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->jsonb('datos_consumidor')->change();
        });
    }
};
