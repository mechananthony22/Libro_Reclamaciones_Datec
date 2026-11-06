<?php

namespace App\Enums;

enum EstadoSuscripcion: string
{
    case DePrueba = 'de_prueba';
    case Activa = 'activa';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::DePrueba => 'Período de prueba',
            self::Activa => 'Suscripción activa',
            self::Vencida => 'Vencida',
            self::Cancelada => 'Cancelada',
        };
    }
}
