<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para el registro y auditoría de envíos de correos y notificaciones.
     * Garantiza el control de reintentos, estado de entrega y persistencia independiente del expediente.
     */
    public function up(): void
    {
        Schema::create('reclamacion_envios', function (Blueprint $table) {
            $table->id();

            // Aislamiento multitenant y relación con la reclamación
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->constrained('reclamaciones')->cascadeOnDelete();

            // Clasificación del envío (constancia_recepcion, respuesta_formal)
            $table->string('tipo', 50)->default('constancia_recepcion');

            // Destinatario y canal
            $table->string('destinatario', 255);
            $table->string('canal', 30)->default('email');

            // Estado de entrega (pendiente, enviado, fallido)
            $table->string('estado', 30)->default('pendiente')->index();
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->text('error_mensaje')->nullable();

            $table->timestamp('enviado_en')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'reclamacion_id']);
            $table->index(['estado', 'intentos']);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla de envíos.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamacion_envios');
    }
};
