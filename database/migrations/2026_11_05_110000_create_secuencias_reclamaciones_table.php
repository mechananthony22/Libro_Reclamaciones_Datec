<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para el control atómico de secuencias correlativas
     * por empresa, ámbito/establecimiento y año calendario según normativa de Indecopi.
     */
    public function up(): void
    {
        Schema::create('secuencias_reclamaciones', function (Blueprint $table) {
            $table->id();

            // Aislamiento por empresa (multitenant)
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();

            // Ámbito de numeración: 'GENERAL' para la empresa o identificador de sucursal/sede
            $table->string('codigo_ambito', 50)->default('GENERAL');

            // Año calendario de emisión de la hoja de reclamación
            $table->unsignedSmallInteger('anio');

            // Último correlativo emitido
            $table->unsignedInteger('ultimo_numero')->default(0);

            $table->timestamps();

            // Restricción única que garantiza la atomicidad y evita secuencias duplicadas
            $table->unique(['empresa_id', 'codigo_ambito', 'anio'], 'secuencias_empresa_ambito_anio_unique');
        });

        // Asegurar unicidad estricta del número de hoja emitido dentro del ámbito de cada empresa
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->unique(['empresa_id', 'numero_hoja'], 'reclamaciones_empresa_numero_hoja_unique');
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla de secuencias
     * y la restricción única de número de hoja.
     */
    public function down(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->dropUnique('reclamaciones_empresa_numero_hoja_unique');
        });

        Schema::dropIfExists('secuencias_reclamaciones');
    }
};
