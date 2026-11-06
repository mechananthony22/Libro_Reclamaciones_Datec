<?php

namespace App\Models;

use App\Enums\DecisionRespuesta;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de la respuesta formal emitida por el proveedor frente a un reclamo o queja.
 * Almacena la decisión motivada, fundamentación, archivos adjuntos de descargo y auditoría de autor.
 */
class ReclamacionRespuesta extends Model
{
    use BelongsToEmpresa;
    use HasFactory;

    protected $table = 'reclamacion_respuestas';

    protected $fillable = [
        'empresa_id',
        'reclamacion_id',
        'user_id',
        'autor_nombre',
        'autor_cargo',
        'decision',
        'propuesta_solucion',
        'fundamento_legal',
        'version',
        'adjuntos_paths',
        'fecha_respuesta',
        'notificado',
        'notificado_en',
    ];

    protected function casts(): array
    {
        return [
            'decision' => DecisionRespuesta::class,
            'fecha_respuesta' => 'datetime',
            'notificado' => 'boolean',
            'notificado_en' => 'datetime',
            'adjuntos_paths' => 'array',
        ];
    }

    /**
     * Reclamación a la que pertenece esta respuesta formal.
     */
    public function reclamacion(): BelongsTo
    {
        return $this->belongsTo(Reclamacion::class, 'reclamacion_id');
    }

    /**
     * Usuario interno responsable que elaboró la respuesta.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
