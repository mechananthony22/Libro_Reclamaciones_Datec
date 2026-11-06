<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de una entrada en la bitácora central de auditoría del sistema y de los tenants.
 * Registra de forma inmutable altas, modificaciones de configuración, accesos privilegiados,
 * respuestas a expedientes, exportaciones de datos y transiciones de estado.
 */
class AuditoriaLog extends Model
{
    use HasFactory;

    protected $table = 'auditoria_logs';

    public $timestamps = false;

    /**
     * Atributos asignables de forma masiva.
     *
     * @var array<string>
     */
    protected $fillable = [
        'empresa_id',
        'user_id',
        'actor_tipo',
        'actor_nombre',
        'actor_email',
        'modulo',
        'accion',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip',
        'user_agent',
        'created_at',
    ];

    /**
     * Conversión y tipado estricto de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'datos_despues' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Empresa asociada al evento de auditoría (opcional para acciones globales de superadministrador).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Usuario que ejecutó la acción auditada.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

