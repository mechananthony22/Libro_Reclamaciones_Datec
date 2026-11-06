<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de respuestas formales para expedientes de reclamación.
     * Almacena la decisión, fundamentación, archivos adjuntos, autor y estado de notificación.
     */
    public function up(): void
    {
        Schema::create('reclamacion_respuestas', function (Blueprint $table) {
            $table->id();

            // Aislamiento multitenant y relación con la reclamación
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->constrained('reclamaciones')->cascadeOnDelete();

            // Autor o usuario interno que elabora la respuesta
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autor_nombre', 150);
            $table->string('autor_cargo', 100)->nullable();

            // Sentido o decisión de la respuesta formal
            $table->string('decision', 30)->default('procedente'); // procedente, improcedente, acuerdo, informativa

            // Fundamentación y propuesta de solución formal
            $table->text('propuesta_solucion');
            $table->text('fundamento_legal')->nullable();

            // Versión de la respuesta para control de revisiones
            $table->unsignedSmallInteger('version')->default(1);

            // Archivos adjuntos probatorios de la respuesta (guardados como array JSON)
            $table->jsonb('adjuntos_paths')->nullable();

            // Marca temporal oficial de elaboración
            $table->timestamp('fecha_respuesta');

            // Estado de notificación al consumidor
            $table->boolean('notificado')->default(false);
            $table->timestamp('notificado_en')->nullable();

            $table->timestamps();

            $table->index(['empresa_id', 'reclamacion_id']);
        });
    }

    /**
     * Revierte las operaciones de migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamacion_respuestas');
    }
};
