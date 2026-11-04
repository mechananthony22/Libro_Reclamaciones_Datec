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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Identificación de empresa
            $table->string('name');
            $table->string('slug')->unique();

            // Información legal
            $table->string('razon_social');
            $table->string('ruc', 11)->unique();
            $table->text('direccion')->nullable();

            // Contacto
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();

            // Personalización
            $table->string('logo_path')->nullable();
            $table->string('color_primario', 7)->default('#2563EB');

            // Estado
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
