<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la configuración de correos de notificación y preferencias por tenant.
     * Permite a cada empresa definir sus destinatarios internos y alertas sin desactivar avisos críticos de ley.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Lista de correos electrónicos adicionales para recibir notificaciones operativas
            $table->jsonb('emails_notificaciones')->nullable();

            // Preferencias de alertas configurables
            $table->boolean('notificar_nuevo_reclamo')->default(true);
            $table->boolean('notificar_vencimiento_preventivo')->default(true);

            // Aviso crítico normativo (no desactivable por la empresa)
            $table->boolean('notificar_vencido')->default(true);

            // Días hábiles de anticipación para alertas preventivas (1 a 5 días hábiles)
            $table->unsignedSmallInteger('dias_anticipacion_alerta')->default(3);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando los campos de configuración.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'emails_notificaciones',
                'notificar_nuevo_reclamo',
                'notificar_vencimiento_preventivo',
                'notificar_vencido',
                'dias_anticipacion_alerta',
            ]);
        });
    }
};
