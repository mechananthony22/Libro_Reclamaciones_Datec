<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de un evento de contingencia, periodo de mantenimiento
 * técnico o ensayo de recuperación de desastres (DR Rehearsal).
 */
class ContingenciaIncidente extends Model
{
    use HasFactory;

    protected $table = 'contingencia_incidentes';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'titulo',
        'descripcion',
        'canal_alternativo',
        'iniciado_en',
        'finalizado_en',
        'iniciado_por_id',
        'finalizado_por_id',
        'resultado_ensayo',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'canal_alternativo' => 'array',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    /**
     * Empresa asociada al incidente o contingencia (null para contingencias globales).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Usuario que inició la contingencia o ensayo.
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por_id');
    }

    /**
     * Usuario que finalizó la contingencia o ensayo.
     */
    public function finalizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por_id');
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function isEnsayo(): bool
    {
        return $this->tipo === 'ensayo_recuperacion';
    }
}
