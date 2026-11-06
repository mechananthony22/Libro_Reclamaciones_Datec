<?php

namespace App\Enums;

enum TipoBien: string
{
    case Producto = 'producto';
    case Servicio = 'servicio';

    public function label(): string
    {
        return match ($this) {
            self::Producto => 'Producto',
            self::Servicio => 'Servicio',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Producto => 'Bien material o artículo físico o digital entregado.',
            self::Servicio => 'Prestación, labor técnica, soporte o actividad brindada.',
        };
    }
}
