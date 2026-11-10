<?php

namespace App\Enums;

enum RolEmpresa: string
{
    case AdminEmpresa = 'admin_empresa';

    public function label(): string
    {
        return match ($this) {
            self::AdminEmpresa => 'Administrador de Empresa',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::AdminEmpresa => 'Control total de la empresa: atiende y responde reclamos, configura sedes y gestiona la identidad legal.',
        };
    }
}
