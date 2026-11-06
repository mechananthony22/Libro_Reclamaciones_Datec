<?php

namespace App\Enums;

enum DecisionRespuesta: string
{
    case Procedente = 'procedente';
    case ProcedenteParcial = 'procedente_parcial';
    case Improcedente = 'improcedente';
    case AcuerdoMutuo = 'acuerdo_mutuo';
    case Informativa = 'informativa';

    public function label(): string
    {
        return match ($this) {
            self::Procedente => 'Procedente (Aceptado)',
            self::ProcedenteParcial => 'Procedente Parcial',
            self::Improcedente => 'Improcedente (Rechazado)',
            self::AcuerdoMutuo => 'Acuerdo Mutuo / Transacción',
            self::Informativa => 'Respuesta Informativa / Aclaratoria',
        };
    }
}
