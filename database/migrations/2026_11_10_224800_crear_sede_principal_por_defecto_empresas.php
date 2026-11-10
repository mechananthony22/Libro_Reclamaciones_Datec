<?php

use App\Models\Empresa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Empresa::all() as $empresa) {
            $tienePrincipal = $empresa->establecimientos()->where('es_principal', true)->exists();
            if (! $tienePrincipal) {
                // Si ya tiene establecimientos, convertir el primero en principal; si no, crear la Sede Principal con la dirección de la empresa
                $primerEstablecimiento = $empresa->establecimientos()->orderBy('id')->first();
                if ($primerEstablecimiento && $primerEstablecimiento->nombre !== 'TIENDA ARBOLITO 2') {
                    $primerEstablecimiento->update(['es_principal' => true]);
                } else {
                    $empresa->establecimientos()->create([
                        'codigo' => 'SEDE-01',
                        'nombre' => 'Sede Principal',
                        'direccion' => $empresa->direccion ?: 'Sede Central',
                        'es_principal' => true,
                        'activo' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        //
    }
};
