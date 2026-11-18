<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
     * Genera un código correlativo único y seguro por fecha (formato: TCK-YYYYMMDD-0001).
     * Evita colisiones por condición de carrera inspeccionando el último registro numérico del día
     * bajo contexto transaccional con bloqueo pesimista y ordenamiento por código correlativo.
     */
    public static function generarCodigoTicket(): string
    {
        $prefijo = 'TCK-'.now()->format('Ymd').'-';

        $calcular = function () use ($prefijo) {
            $ultimo = static::query()
                ->where('codigo_ticket', 'like', "{$prefijo}%")
                ->lockForUpdate()
                ->orderByDesc('codigo_ticket')
                ->value('codigo_ticket');

            $siguiente = 1;
            if ($ultimo) {
                $partes = explode('-', (string) $ultimo);
                $correlativoStr = end($partes);
                $siguiente = ((int) $correlativoStr) + 1;
            }

            return $prefijo.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
        };

        return DB::transactionLevel() > 0 ? $calcular() : DB::transaction($calcular);
    }

    /**
     * Expira automáticamente todas las autorizaciones vigentes cuya fecha límite ya venció.
     * Transiciona el estado de 'autorizado' a 'expirado'.
     */
    public static function expirarVencidos(): int
    {
        return static::where('estado', 'autorizado')
            ->whereNotNull('valido_hasta')
            ->where('valido_hasta', '<=', now())
            ->update([
                'estado' => 'expirado',
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

    /**
     * Rechaza una solicitud de soporte o acceso delegado en estado pendiente.
     */
    public function rechazar(User $rechazador, string $motivo = 'Solicitud de soporte rechazada por la empresa'): void
    {
        $this->update([
            'estado' => 'rechazado',
            'motivo_revocacion' => $motivo,
            'valido_hasta' => now(),
        ]);
    }
}
