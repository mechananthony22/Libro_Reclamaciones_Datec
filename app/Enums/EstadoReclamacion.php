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
            self::Pendiente => 'Pendiente / Recibido',
            self::EnTramite => 'En trámite / En revisión',
            self::Atendida => 'Atendida / Respuesta enviada',
            self::Cerrada => 'Cerrada',
        };
    }

    /**
     * Define las transiciones de estado permitidas en el ciclo de vida del reclamo.
     *
     * @return array<string>
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Pendiente => [self::EnTramite->value, self::Atendida->value],
            self::EnTramite => [self::Atendida->value, self::Pendiente->value],
            self::Atendida => [self::Cerrada->value, self::EnTramite->value],
            self::Cerrada => [self::EnTramite->value],
        };
    }

    public function puedeTransitarHacia(string|self $nuevoEstado): bool
    {
        $valor = $nuevoEstado instanceof self ? $nuevoEstado->value : $nuevoEstado;

        return in_array($valor, $this->transicionesPermitidas(), true);
    }
}
