<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo representativo de una alerta operativa de disponibilidad,
 * saturación de colas, errores o fallos de mensajería para Datec.
 */
class PlataformaAlerta extends Model
{
    use HasFactory;

    protected $table = 'plataforma_alertas';

    protected $fillable = [
        'tipo',
        'severidad',
        'titulo',
        'descripcion',
        'valor_actual',
        'umbral_definido',
        'estado',
        'notificado_datec_en',
        'resuelta_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notificado_datec_en' => 'datetime',
            'resuelta_en' => 'datetime',
        ];
    }

    public function isActiva(): bool
    {
        return $this->estado === 'activa';
    }

    public function isCritica(): bool
    {
        return $this->severidad === 'critica';
    }
}
