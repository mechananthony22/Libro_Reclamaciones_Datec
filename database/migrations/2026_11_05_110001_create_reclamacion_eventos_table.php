<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla de eventos y auditoría del expediente.
     * Esta tabla conserva la trazabilidad inmutable de la presentación original
     * y cualquier gestión, respuesta o cambio de estado posterior.
     */
    public function up(): void
    {
        Schema::create('reclamacion_eventos', function (Blueprint $table) {
            $table->id();

            // Aislamiento multitenant estricto y relación con el expediente
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->constrained('reclamaciones')->cascadeOnDelete();

            // Clasificación del actor que origina el evento (consumidor, usuario interno, sistema)
            $table->string('actor_tipo', 30)->default('consumidor');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_nombre', 150)->nullable();

            // Identificador de la acción registrada
            $table->string('accion', 50)->index();

            // Descripción legible del evento para visualización en el historial del expediente
            $table->text('descripcion');

            // Instantánea de datos antes y después del evento para auditoría forense
            $table->jsonb('datos_antes')->nullable();
            $table->jsonb('datos_despues')->nullable();

            // Metadatos de red y cliente para trazabilidad de seguridad
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices de consulta rápida para el historial del reclamo
            $table->index(['empresa_id', 'reclamacion_id']);
            $table->index(['reclamacion_id', 'created_at']);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla de eventos.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamacion_eventos');
    }
};
