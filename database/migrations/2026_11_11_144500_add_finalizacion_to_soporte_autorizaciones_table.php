<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soporte_autorizaciones', function (Blueprint $table) {
            $table->timestamp('finalizado_en')->nullable()->after('revocado_en');
            $table->text('resolucion_detalle')->nullable()->after('finalizado_en');
            $table->foreignId('finalizado_por_id')->nullable()->after('resolucion_detalle')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('soporte_autorizaciones', function (Blueprint $table) {
            $table->dropForeign(['finalizado_por_id']);
            $table->dropColumn(['finalizado_en', 'resolucion_detalle', 'finalizado_por_id']);
        });
    }
};
