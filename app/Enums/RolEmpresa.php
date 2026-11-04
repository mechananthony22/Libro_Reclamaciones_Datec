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
            self::AdminEmpresa => 'Configura razón social, sedes, marca, usuarios y reglas de la empresa.',
            self::GestorReclamaciones => 'Revisa, responde, adjunta evidencias y cierra reclamaciones de la empresa.',
            self::LectorEmpresa => 'Consulta expedientes y exporta reportes con acceso de solo lectura.',
        };
    }
}
