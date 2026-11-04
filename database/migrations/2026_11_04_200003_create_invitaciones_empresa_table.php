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
        Schema::create('invitaciones_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('email');
            $table->string('nombre')->nullable();
            $table->string('rol', 50)->default('admin_empresa');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitaciones_empresa');
    }
};

