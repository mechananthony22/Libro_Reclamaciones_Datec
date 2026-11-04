<?php

namespace App\Models;

use App\Enums\EstadoReclamacion;
use App\Enums\TipoBien;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de una reclamación o queja virtual presentada por un consumidor.
 * Implementa el trait BelongsToEmpresa para garantizar el aislamiento multitenant estricto en PostgreSQL.
 */
class Reclamacion extends Model
{
    use HasFactory;
    use BelongsToEmpresa;

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
        'estado',
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
            'vence_en' => 'date',
            'tipo_bien' => TipoBien::class,
            'estado' => EstadoReclamacion::class,
            'monto_reclamado' => 'decimal:2',
            'datos_consumidor' => 'array',
            'datos_legales_snapshot' => 'array',
        ];
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
}
