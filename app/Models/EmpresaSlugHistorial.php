<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de un slug o identificador permanente previo de una empresa.
 * Mantiene la reserva histórica del identificador para redirecciones permanentes 301.
 */
class EmpresaSlugHistorial extends Model
{
    use HasFactory;

    protected $table = 'empresa_slug_historial';

    protected $fillable = [
        'empresa_id',
        'slug_antiguo',
        'motivo',
        'cambiado_por_id',
    ];

    /**
     * Empresa a la que perteneció históricamente este slug.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Usuario que efectuó o autorizó la reconfiguración del slug.
     */
    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por_id');
    }
}
