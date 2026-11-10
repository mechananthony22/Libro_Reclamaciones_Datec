<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Incorpora campos operativos de contingencia técnica, canales alternativos,
     * registro de baja de clientes y custodia por política de retención legal.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Contingencia y canales alternativos de atención
            $table->boolean('en_contingencia')->default(false)->after('dias_anticipacion_alerta');
            $table->text('contingencia_mensaje')->nullable()->after('en_contingencia');
            $table->jsonb('contingencia_canal_alternativo')->nullable()->after('contingencia_mensaje');
            $table->timestamp('contingencia_activada_en')->nullable()->after('contingencia_canal_alternativo');
            $table->timestamp('contingencia_estimada_fin')->nullable()->after('contingencia_activada_en');

            // Desactivación, salida de cliente y política de retención legal
            $table->timestamp('fecha_baja')->nullable()->after('contingencia_estimada_fin');
            $table->text('motivo_baja')->nullable()->after('fecha_baja');
            $table->date('retencion_hasta')->nullable()->after('motivo_baja');
            $table->foreignId('salida_autorizada_por_id')->nullable()->after('retencion_hasta')->constrained('users')->nullOnDelete();
            $table->timestamp('paquete_salida_generado_en')->nullable()->after('salida_autorizada_por_id');
            $table->string('paquete_salida_hash', 64)->nullable()->after('paquete_salida_generado_en');
        });
    }

    /**
     * Revierte las modificaciones efectuadas en la tabla tenants.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['salida_autorizada_por_id']);
            $table->dropColumn([
                'en_contingencia',
                'contingencia_mensaje',
                'contingencia_canal_alternativo',
                'contingencia_activada_en',
                'contingencia_estimada_fin',
                'fecha_baja',
                'motivo_baja',
                'retencion_hasta',
                'salida_autorizada_por_id',
                'paquete_salida_generado_en',
                'paquete_salida_hash',
            ]);
        });
    }
};
