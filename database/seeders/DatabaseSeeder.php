<?php

namespace Database\Seeders;

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de configuración inicial de roles y usuarios de prueba:
 * 1. Superadministrador Datec (global): datecsuperadmin@gmail.com
 * 2. Administrador de Empresa 1: admin@datec.pe (asignado a Datec Consulting SAC)
 * 3. Administrador de Empresa 2: admin@novatech.pe (asignado a NovaTech Soluciones SAC)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta las semillas en la base de datos.
     */
    public function run(): void
    {
        // 1. Crear o actualizar la empresa principal (Datec Consulting)
        $empresaDatec = Empresa::firstOrCreate(
            ['slug' => 'datec-2'],
            [
                'name' => 'Datec Consulting',
                'razon_social' => 'Datec Consulting S.A.C.',
                'ruc' => '20601234567',
                'direccion' => 'Av. Pedro Ruiz 450, Chiclayo, Lambayeque',
                'email' => 'contacto@datec.pe',
                'telefono' => '917144944',
                'color_primario' => '#2563EB',
                'activo' => true,
                'estado' => EstadoEmpresa::Activa,
                'plan' => PlanEmpresa::Pro,
            ]
        );

        // 2. Crear una segunda empresa de prueba para demostrar multitenancy
        $empresaNova = Empresa::firstOrCreate(
            ['slug' => 'novatech-peru'],
            [
                'name' => 'NovaTech Soluciones',
                'razon_social' => 'NovaTech Soluciones Empresariales S.A.C.',
                'ruc' => '20789456123',
                'direccion' => 'Av. Rivera Navarrete 765, San Isidro, Lima',
                'email' => 'hola@novatech.pe',
                'telefono' => '01-4455889',
                'color_primario' => '#059669',
                'activo' => true,
                'estado' => EstadoEmpresa::Activa,
                'plan' => PlanEmpresa::Corporativo,
            ]
        );

        // 3. Crear el SUPERADMINISTRADOR único global de Datec
        $superAdmin = User::updateOrCreate(
            ['email' => 'datecsuperadmin@gmail.com'],
            [
                'name' => 'Superadmin Datec',
                'password' => Hash::make('password123'),
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ]
        );

        // 4. Crear el Administrador de Prueba 1 (Asignado a Datec Consulting)
        $adminDatec = User::updateOrCreate(
            ['email' => 'admin@datec.pe'],
            [
                'name' => 'Carlos Administrador Datec',
                'password' => Hash::make('password123'),
                'is_superadmin' => false,
                'email_verified_at' => now(),
            ]
        );

        $adminDatec->empresas()->syncWithoutDetaching([
            $empresaDatec->id => [
                'rol' => RolEmpresa::AdminEmpresa->value,
                'activo' => true,
            ],
        ]);

        // 5. Crear el Administrador de Prueba 2 (Asignado a NovaTech y también con acceso a Datec como auditor)
        $adminNova = User::updateOrCreate(
            ['email' => 'admin@novatech.pe'],
            [
                'name' => 'Mariana Administradora NovaTech',
                'password' => Hash::make('password123'),
                'is_superadmin' => false,
                'email_verified_at' => now(),
            ]
        );

        $adminNova->empresas()->syncWithoutDetaching([
            $empresaNova->id => [
                'rol' => RolEmpresa::AdminEmpresa->value,
                'activo' => true,
            ],
        ]);
    }
}
