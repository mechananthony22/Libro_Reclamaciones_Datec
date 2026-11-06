<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    use BelongsToEmpresa;

    protected $table = 'establecimientos';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'direccion',
        'es_principal',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
