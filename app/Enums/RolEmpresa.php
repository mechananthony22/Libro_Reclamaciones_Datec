<?php

namespace App\Enums;

enum RolEmpresa: string
{
    case AdminEmpresa = 'admin_empresa';
    case GestorReclamaciones = 'gestor_reclamaciones';
    case LectorEmpresa = 'lector_empresa';

    public function label(): string
    {
        return match ($this) {
            self::AdminEmpresa => 'Administrador de Empresa',
            self::GestorReclamaciones => 'Gestor de Reclamaciones',
            self::LectorEmpresa => 'Lector / Auditor',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::AdminEmpresa => 'Control total de la empresa: atiende y responde reclamos, configura sedes y gestiona la identidad legal.',
            self::GestorReclamaciones => 'Revisa, atiende, responde reclamos y gestiona expedientes de la empresa.',
            self::LectorEmpresa => 'Consulta expedientes y bitácoras de auditoría con acceso de solo lectura.',
        };
    }
}
