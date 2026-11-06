<?php

namespace App\Enums;

enum TipoDocumentoConsumidor: string
{
    case DNI = 'dni';
    case CarnetExtranjeria = 'ce';
    case Pasaporte = 'pasaporte';
    case RUC = 'ruc';

    public function label(): string
    {
        return match ($this) {
            self::DNI => 'DNI - Doc. Nacional de Identidad',
            self::CarnetExtranjeria => 'Carné de Extranjería',
            self::Pasaporte => 'Pasaporte',
            self::RUC => 'RUC',
        };
    }

    public function longitudExacta(): ?int
    {
        return match ($this) {
            self::DNI => 8,
            self::RUC => 11,
            default => null,
        };
    }
}
