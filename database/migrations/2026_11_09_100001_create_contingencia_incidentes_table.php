<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para registrar formalmente los incidentes de contingencia,
     * periodos de mantenimiento programado y ensayos técnicos de recuperación.
     */
    public function up(): void
    {
        Schema::create('contingencia_incidentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('tipo', 40); // incidente_real, ensayo_recuperacion, mantenimiento_programado
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->jsonb('canal_alternativo')->nullable();
            $table->timestamp('iniciado_en');
            $table->timestamp('finalizado_en')->nullable();
            $table->foreignId('iniciado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resultado_ensayo')->nullable();
            $table->string('estado', 30)->default('activo'); // activo, resuelto, completado
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index(['tipo', 'iniciado_en']);
        });
    }

    /**
     * Revierte la creación de la tabla contingencia_incidentes.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencia_incidentes');
    }
};
