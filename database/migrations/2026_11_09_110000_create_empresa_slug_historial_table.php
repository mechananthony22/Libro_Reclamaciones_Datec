<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para almacenar el historial de slugs antiguos de cada empresa cliente,
     * garantizando redirecciones permanentes seguras (HTTP 301) y evitando colisiones
     * o asignaciones indebidas a otros proveedores.
     */
    public function up(): void
    {
        Schema::create('empresa_slug_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('slug_antiguo', 100)->unique();
            $table->text('motivo')->nullable();
            $table->foreignId('cambiado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
        });
    }

    /**
     * Revierte las modificaciones efectuadas.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_slug_historial');
    }
};
