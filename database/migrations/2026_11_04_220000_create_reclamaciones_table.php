<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla de reclamaciones.
     * Esta tabla persiste las reclamaciones y quejas registradas por los consumidores
     * asegurando el aislamiento multitenant y la trazabilidad inmutable del proveedor.
     */
    public function up(): void
    {
        Schema::create('reclamaciones', function (Blueprint $table) {
            $table->id();

            // Claves foráneas de aislamiento multitenant y sucursal
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('establecimiento_id')->nullable()->constrained('establecimientos')->nullOnDelete();

            // Identificación correlativa única por empresa y marca temporal oficial
            $table->string('numero_hoja', 50)->nullable()->index();
            $table->timestamp('fecha_hora');

            // Clasificación del caso: reclamo o queja
            $table->string('tipo', 20)->default('reclamo')->index();

            // Identificación y características del bien contratado (Producto o Servicio)
            $table->string('tipo_bien', 20)->index();
            $table->decimal('monto_reclamado', 12, 2)->nullable();
            $table->string('moneda', 3)->default('PEN');
            $table->text('descripcion_bien');
            $table->string('numero_comprobante', 100)->nullable();
            $table->date('fecha_contratacion')->nullable();

            // Hechos y pedido concreto del consumidor
            $table->text('detalle_problema')->nullable();
            $table->text('pedido_consumidor')->nullable();

            // Estructuras JSON para datos del consumidor y snapshot legal del proveedor al registrar
            $table->jsonb('datos_consumidor');
            $table->jsonb('datos_legales_snapshot');

            // Estado del expediente, fecha límite de atención (15 días hábiles) y token de consulta
            $table->string('estado', 30)->default('pendiente')->index();
            $table->date('vence_en')->nullable()->index();
            $table->string('token_consulta', 64)->nullable()->unique();

            $table->timestamps();

            // Índices de optimización para búsquedas por empresa y estado
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'numero_hoja']);
        });
    }

    /**
     * Revierte las operaciones de migración eliminando la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamaciones');
    }
};
