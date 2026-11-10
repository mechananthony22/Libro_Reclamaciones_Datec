<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('invitaciones_empresa')
            ->whereIn('rol', ['gestor_reclamaciones', 'lector_empresa'])
            ->update(['rol' => 'admin_empresa']);

        DB::table('empresa_usuario')
            ->whereIn('rol', ['gestor_reclamaciones', 'lector_empresa'])
            ->update(['rol' => 'admin_empresa']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible
    }
};
