<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para autorizaciones de soporte técnico y acceso delegado.
     * Implementa la regla de negocio de acceso excepcional, temporal y auditado a los expedientes.
     */
    public function up(): void
    {
        Schema::create('soporte_autorizaciones', function (Blueprint $table) {
            $table->id();

            // Empresa a la que pertenece el ticket y autorización
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();

            // Código correlativo legible del ticket
            $table->string('codigo_ticket', 40)->unique();
            $table->string('titulo', 150);
            $table->text('motivo');

            // Actor que solicita el soporte y actor que autoriza el acceso
            $table->foreignId('solicitado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizado_por_id')->nullable()->constrained('users')->nullOnDelete();

            // Estado del ticket: pendiente, autorizado, rechazado, revocado, expirado
            $table->string('estado', 30)->default('pendiente')->index();

            // Periodo de vigencia temporal
            $table->unsignedInteger('horas_vigencia')->default(24);
            $table->timestamp('autorizado_en')->nullable();
            $table->timestamp('valido_hasta')->nullable()->index();

            // Revocación anticipada
            $table->timestamp('revocado_en')->nullable();
            $table->text('motivo_revocacion')->nullable();

            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla de soporte.
     */
    public function down(): void
    {
        Schema::dropIfExists('soporte_autorizaciones');
    }
};
