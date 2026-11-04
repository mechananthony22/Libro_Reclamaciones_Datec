<?php

namespace App\Enums;

enum EstadoEmpresa: string
{
    case Borrador = 'borrador';
    case Activa = 'activa';
    case Suspendida = 'suspendida';
    case BajaProgramada = 'baja_programada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Activa => 'Activa',
            self::Suspendida => 'Suspendida',
            self::BajaProgramada => 'Baja programada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Activa => 'green',
            self::Suspendida => 'amber',
            self::BajaProgramada => 'red',
        };
    }
}

