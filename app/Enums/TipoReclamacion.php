<?php

namespace App\Enums;

/**
 * Clasificación de la reclamación conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571).
 */
enum TipoReclamacion: string
{
    case Reclamo = 'reclamo';
    case Queja = 'queja';

    /**
     * Nombre formal y legible de la clasificación.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reclamo => 'Reclamo',
            self::Queja => 'Queja',
        };
    }

    /**
     * Descripción oficial orientadora para el consumidor según INDECOPI.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::Reclamo => 'Disconformidad directamente relacionada con los productos o servicios adquiridos o contratados.',
            self::Queja => 'Malestar o descontento respecto a la atención al público brindada por el personal o aspectos no relacionados directamente con el bien.',
        };
    }
}
