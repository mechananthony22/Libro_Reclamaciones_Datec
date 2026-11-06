<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la columna de usuario responsable asignado a la tabla de reclamaciones.
     */
    public function up(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->foreignId('asignado_a')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->timestamp('asignado_en')->nullable()->after('asignado_a');

            $table->index(['empresa_id', 'asignado_a']);
        });
    }

    /**
     * Revierte las operaciones de migración.
     */
    public function down(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->dropForeign(['asignado_a']);
            $table->dropIndex(['empresa_id', 'asignado_a']);
            $table->dropColumn(['asignado_a', 'asignado_en']);
        });
    }
};
