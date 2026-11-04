<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Representante operativo (RF-001)
            $table->string('representante_nombre')->nullable();
            $table->string('representante_email')->nullable();
            $table->string('representante_telefono', 30)->nullable();

            // Estados del servicio (RF-002: borrador, activa, suspendida, baja_programada)
            $table->string('estado', 30)->default('borrador');
            $table->text('motivo_cambio_estado')->nullable();
            $table->timestamp('fecha_cambio_estado')->nullable();

            // Planes y vigencia (RF-003: basico, pro, corporativo)
            $table->string('plan', 50)->default('basico');
            $table->string('estado_suscripcion', 30)->default('activa');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->unsignedInteger('limite_reclamaciones_mes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'representante_nombre',
                'representante_email',
                'representante_telefono',
                'estado',
                'motivo_cambio_estado',
                'fecha_cambio_estado',
                'plan',
                'estado_suscripcion',
                'vigencia_desde',
                'vigencia_hasta',
                'limite_reclamaciones_mes',
            ]);
        });
    }
};
