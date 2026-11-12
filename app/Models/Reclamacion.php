<?php

namespace App\Models;

use App\Enums\EstadoReclamacion;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\Concerns\BelongsToEmpresa;
use App\Services\PlazoReclamacionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo representativo de una reclamación o queja virtual presentada por un consumidor.
 * Implementa el trait BelongsToEmpresa para garantizar el aislamiento multitenant estricto en PostgreSQL.
 */
class Reclamacion extends Model
{
    use BelongsToEmpresa;
    use HasFactory;

    protected $table = 'reclamaciones';

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'empresa_id',
        'establecimiento_id',
        'numero_hoja',
        'fecha_hora',
        'tipo',
        'tipo_bien',
        'monto_reclamado',
        'moneda',
        'descripcion_bien',
        'numero_comprobante',
        'fecha_contratacion',
        'detalle_problema',
        'pedido_consumidor',
        'datos_consumidor',
        'datos_legales_snapshot',
        'aviso_privacidad_version',
        'aviso_privacidad_aceptado_en',
        'aviso_privacidad_ip',
        'estado',
        'asignado_a',
        'asignado_en',
        'vence_en',
        'token_consulta',
    ];

    /**
     * Conversión y tipado estricto de atributos a nivel de modelo.
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'fecha_contratacion' => 'date',
            'asignado_en' => 'datetime',
            'vence_en' => 'date',
            'aviso_privacidad_aceptado_en' => 'datetime',
            'tipo' => TipoReclamacion::class,
            'tipo_bien' => TipoBien::class,
            'estado' => EstadoReclamacion::class,
            'monto_reclamado' => 'decimal:2',
            'datos_consumidor' => 'array',
            'datos_legales_snapshot' => 'array',
        ];
    }

    /**
     * Reglas del modelo para salvaguardar la integridad de los expedientes.
     * Bloquea terminantemente la eliminación destructiva ordinaria para cumplir con el marco de custodia legal.
     */
    protected static function booted(): void
    {
        static::creating(function (Reclamacion $reclamacion) {
            if (empty($reclamacion->vence_en)) {
                $fechaBase = $reclamacion->fecha_hora ?? now();
                $reclamacion->vence_en = app(PlazoReclamacionService::class)
                    ->calcularFechaVencimiento($fechaBase, 15);
            }
        });

        static::deleting(function (Reclamacion $reclamacion) {
            throw new \DomainException('La eliminación de reclamaciones está estrictamente prohibida por normativa legal (D.S. N° 011-2011-PCM / Ley N° 29571). Los expedientes deben conservarse de forma íntegra e inmutable.');
        });
    }

    /**
     * Empresa o comercio al que pertenece la reclamación.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Establecimiento o sucursal donde ocurrió la adquisición o contratación.
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    /**
     * Usuario de la empresa asignado como responsable del expediente.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    /**
     * Historial cronológico inmutable de eventos y auditoría del expediente.
     */
    public function eventos(): HasMany
    {
        return $this->hasMany(ReclamacionEvento::class, 'reclamacion_id')->orderBy('created_at', 'asc');
    }

    /**
     * Evidencias y documentos adjuntos al expediente.
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(ReclamacionAdjunto::class, 'reclamacion_id');
    }

    /**
     * Historial de notificaciones y correos transaccionales generados para el expediente.
     */
    public function envios(): HasMany
    {
        return $this->hasMany(ReclamacionEnvio::class, 'reclamacion_id');
    }

    /**
     * Respuestas formales emitidas para la atención del expediente.
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(ReclamacionRespuesta::class, 'reclamacion_id')->orderBy('version', 'desc');
    }

    /**
     * Última respuesta formal emitida.
     */
    public function respuestaActual(): ?ReclamacionRespuesta
    {
        return $this->respuestas()->first();
    }
}
