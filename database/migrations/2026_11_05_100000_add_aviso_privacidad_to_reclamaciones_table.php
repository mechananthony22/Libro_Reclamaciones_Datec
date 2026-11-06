<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La trazabilidad del aviso de privacidad
     * y tratamiento de datos personales conforme a la Ley N° 29733.
     */
    public function up(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            // Versión del aviso legal de protección de datos mostrado al consumidor
            $table->string('aviso_privacidad_version', 20)->default('1.0')->after('datos_legales_snapshot');

            // Marca temporal de lectura y toma de conocimiento del aviso legal
            $table->timestamp('aviso_privacidad_aceptado_en')->nullable()->after('aviso_privacidad_version');

            // Dirección IP anonimizada o registrada para auditoría del envío
            $table->string('aviso_privacidad_ip', 45)->nullable()->after('aviso_privacidad_aceptado_en');
        });
    }

    /**
     * Revierte las modificaciones agregadas a la tabla de reclamaciones.
     */
    public function down(): void
    {
        Schema::table('reclamaciones', function (Blueprint $table) {
            $table->dropColumn([
                'aviso_privacidad_version',
                'aviso_privacidad_aceptado_en',
                'aviso_privacidad_ip',
            ]);
        });
    }
};
