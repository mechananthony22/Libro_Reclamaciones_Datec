<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('la pantalla de login esta disponible para usuarios no autenticados', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

test('el superadministrador datec inicia sesion y es redirigido al centro de control de empresas', function () {
    $superadmin = User::create([
        'name' => 'Superadmin Datec',
        'email' => 'datecsuperadmin@gmail.com',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'datecsuperadmin@gmail.com',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($superadmin);
    $response->assertRedirect(route('admin.empresas.index'));
});

test('un administrador de empresa inicia sesion y es redirigido al panel de su empresa', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Norte SAC',
        'slug' => 'comercio-norte',
        'razon_social' => 'Comercio Norte S.A.C.',
        'ruc' => '20123987456',
        'direccion' => 'Av. Bolognesi 123, Chiclayo',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $adminEmpresa = User::create([
        'name' => 'Admin Empresa Norte',
        'email' => 'admin@comercionorte.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $adminEmpresa->empresas()->attach($empresa->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'admin@comercionorte.pe',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($adminEmpresa);
    $response->assertRedirect(route('empresa.dashboard'));
    expect(session('current_tenant_id'))->toBe($empresa->id);
});

test('un usuario sin privilegios de superadmin no puede acceder al modulo de admin empresas', function () {
    $adminEmpresa = User::create([
        'name' => 'Admin Simple',
        'email' => 'simple@empresa.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $response = $this->actingAs($adminEmpresa)->get(route('admin.empresas.index'));

    $response->assertForbidden();
});

test('un usuario autenticado puede cerrar sesion correctamente', function () {
    $user = User::create([
        'name' => 'Usuario Logout',
        'email' => 'logout@empresa.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
