<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de una autorización de soporte técnico con acceso delegado temporal.
 * Implementa el principio de privilegio mínimo y la regla de negocio de acceso excepcional
 * y debidamente autorizado a los expedientes y datos de la empresa.
 */
class SoporteAutorizacion extends Model
{
    use HasFactory;

    protected $table = 'soporte_autorizaciones';

    protected $fillable = [
        'empresa_id',
        'codigo_ticket',
        'titulo',
        'motivo',
        'solicitado_por_id',
        'autorizado_por_id',
        'estado',
        'horas_vigencia',
        'autorizado_en',
        'valido_hasta',
        'revocado_en',
        'motivo_revocacion',
        'finalizado_en',
        'resolucion_detalle',
        'finalizado_por_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'horas_vigencia' => 'integer',
            'autorizado_en' => 'datetime',
            'valido_hasta' => 'datetime',
            'revocado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    /**
     * Empresa a la que se le solicita o concede el acceso de soporte técnico.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Usuario que generó o solicitó el ticket de soporte técnico.
     */
    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    /**
     * Usuario administrador de la empresa que autorizó el acceso delegado.
     */
    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por_id');
    }

    /**
     * Usuario que finalizó o resolvió el ticket de soporte técnico.
     */
    public function finalizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por_id');
    }

    /**
     * Determina si la autorización se encuentra activa y vigente.
     */
    public function isActivo(): bool
    {
        if ($this->estado !== 'autorizado') {
            return false;
        }

        if (! $this->valido_hasta) {
            return false;
        }

        return $this->valido_hasta->isFuture();
    }

    /**
     * Autoriza el acceso delegado temporal por la cantidad de horas indicada.
     */
    public function autorizar(User $autorizador, int $horas = 24): void
    {
        $ahora = now();

        $this->update([
            'estado' => 'autorizado',
            'autorizado_por_id' => $autorizador->id,
            'horas_vigencia' => $horas,
            'autorizado_en' => $ahora,
            'valido_hasta' => $ahora->copy()->addHours($horas),
            'revocado_en' => null,
            'motivo_revocacion' => null,
        ]);
    }

    /**
     * Finaliza o marca como resuelto el ticket de soporte técnico.
     */
    public function finalizar(User $finalizador, ?string $resolucion = null): void
    {
        $this->update([
            'estado' => 'finalizado',
            'finalizado_en' => now(),
            'resolucion_detalle' => $resolucion ?: 'Soporte técnico concluido satisfactoriamente.',
            'finalizado_por_id' => $finalizador->id,
            'valido_hasta' => now(), // Cierra el acceso delegado de inmediato
        ]);
    }

    /**
     * Revoca anticipadamente el acceso delegado.
     */
    public function revocar(string $motivo = 'Revocado por el administrador de la empresa'): void
    {
        $this->update([
            'estado' => 'revocado',
            'revocado_en' => now(),
            'motivo_revocacion' => $motivo,
            'valido_hasta' => now(), // Cierra el acceso delegado de inmediato
        ]);
    }
}
