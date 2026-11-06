<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la persistencia de archivos adjuntos y evidencias del consumidor.
     * Garantiza el aislamiento multitenant estricto y la trazabilidad de seguridad del archivo.
     */
    public function up(): void
    {
        Schema::create('reclamacion_adjuntos', function (Blueprint $table) {
            $table->id();

            // Aislamiento multitenant y relación con el expediente
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->constrained('reclamaciones')->cascadeOnDelete();

            // Metadatos originales del archivo subido
            $table->string('nombre_original', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_sha256', 64)->nullable();

            // Clasificación del adjunto (sustento_consumidor, respuesta_proveedor)
            $table->string('origen', 30)->default('consumidor');

            $table->timestamps();

            // Índices de consulta rápida
            $table->index(['empresa_id', 'reclamacion_id']);
        });
    }

    /**
     * Revierte las migraciones eliminando la tabla de adjuntos.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamacion_adjuntos');
    }
};
