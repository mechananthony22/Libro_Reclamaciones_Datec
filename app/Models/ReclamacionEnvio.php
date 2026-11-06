<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de una notificación o envío de correo transaccional
 * vinculado a una reclamación formal.
 */
class ReclamacionEnvio extends Model
{
    use BelongsToEmpresa;
    use HasFactory;

    protected $table = 'reclamacion_envios';

    protected $fillable = [
        'empresa_id',
        'reclamacion_id',
        'tipo',
        'destinatario',
        'canal',
        'estado',
        'intentos',
        'error_mensaje',
        'enviado_en',
    ];

    protected function casts(): array
    {
        return [
            'enviado_en' => 'datetime',
            'intentos' => 'integer',
        ];
    }

    /**
     * Reclamación asociada al envío.
     */
    public function reclamacion(): BelongsTo
    {
        return $this->belongsTo(Reclamacion::class, 'reclamacion_id');
    }

    /**
     * Empresa proveedora a la que pertenece el expediente.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
