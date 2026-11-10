<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para el registro y trazabilidad de alertas operativas de salud,
     * colas asíncronas, uso de recursos y fallos de mensajería para Datec.
     */
    public function up(): void
    {
        Schema::create('plataforma_alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 60)->index();
            $table->string('severidad', 20)->default('advertencia'); // informativa, advertencia, critica
            $table->string('titulo', 180);
            $table->text('descripcion');
            $table->string('valor_actual', 80)->nullable();
            $table->string('umbral_definido', 80)->nullable();
            $table->string('estado', 20)->default('activa')->index(); // activa, resuelta, silenciada
            $table->timestamp('notificado_datec_en')->nullable();
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'severidad']);
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('plataforma_alertas');
    }
};
