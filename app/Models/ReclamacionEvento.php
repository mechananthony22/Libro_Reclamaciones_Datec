<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de un evento o entrada en el historial de auditoría de un expediente.
 * Garantiza la inmutabilidad de la presentación original y registra de forma cronológica
 * cualquier acción, gestión administrativa o comunicación posterior.
 */
class ReclamacionEvento extends Model
{
    use BelongsToEmpresa;
    use HasFactory;

    protected $table = 'reclamacion_eventos';

    public $timestamps = false;

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'empresa_id',
        'reclamacion_id',
        'actor_tipo',
        'actor_id',
        'actor_nombre',
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
     * Expediente de reclamación al que pertenece el evento registrado.
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

    /**
     * Usuario interno responsable de la acción, en caso de haber sido ejecutada por personal autorizado.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
