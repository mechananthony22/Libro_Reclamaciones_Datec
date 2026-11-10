<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para el historial general de auditoría de la plataforma y del tenant.
     * Registra altas, modificaciones de configuración, accesos privilegiados, exportaciones y cambios de estado.
     */
    public function up(): void
    {
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();

            // Tenant asociado a la acción (nulo para acciones de nivel global del superadministrador)
            $table->foreignId('empresa_id')->nullable()->constrained('tenants')->nullOnDelete();

            // Usuario que ejecutó la acción (nulo para acciones del sistema o cron)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Clasificación y metadatos del actor
            $table->string('actor_tipo', 30)->default('usuario');
            $table->string('actor_nombre', 150);
            $table->string('actor_email', 150)->nullable();

            // Módulo y acción específica registrada
            $table->string('modulo', 40)->index();
            $table->string('accion', 60)->index();

            // Descripción legible del evento
            $table->text('descripcion');

            // Instantáneas de datos anteriores y posteriores para auditoría forense
            $table->jsonb('datos_antes')->nullable();
            $table->jsonb('datos_despues')->nullable();

            // Trazabilidad de seguridad de red
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices de consulta rápida
            $table->index(['empresa_id', 'created_at']);
            $table->index(['modulo', 'created_at']);
            $table->index(['accion', 'created_at']);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla de auditoría.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
