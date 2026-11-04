<?php

namespace App\Enums;

enum EstadoReclamacion: string
{
    case Pendiente = 'pendiente';
    case EnTramite = 'en_tramite';
    case Atendida = 'atendida';
    case Cerrada = 'cerrada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnTramite => 'En trámite',
            self::Atendida => 'Atendida',
            self::Cerrada => 'Cerrada',
        };
    }
}

