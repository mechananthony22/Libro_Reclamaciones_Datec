<?php

namespace App\Enums;

enum PlanEmpresa: string
{
    case Basico = 'basico';
    case Pro = 'pro';
    case Corporativo = 'corporativo';

    public function label(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Pro => 'Profesional',
            self::Corporativo => 'Corporativo',
        };
    }
}
