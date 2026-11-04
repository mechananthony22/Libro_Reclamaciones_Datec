<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use App\Models\User;

test('el administrador empresarial puede invitar un usuario a su empresa', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Alfa',
        'slug' => 'empresa-alfa',
        'razon_social' => 'Empresa Alfa S.A.C.',
        'ruc' => '20123456789',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $this->actingAs($admin)
        ->withSession(['empresa_actual_id' => $empresa->id])
        ->post(route('empresa.usuarios.invitar'), [
            'email' => 'operador@alfa.com',
            'rol' => RolEmpresa::GestorReclamaciones->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('invitaciones_empresa', [
        'empresa_id' => $empresa->id,
        'email' => 'operador@alfa.com',
        'rol' => RolEmpresa::GestorReclamaciones->value,
    ]);
});

test('no se pueden asignar roles globales o valores inexistentes', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Beta',
        'slug' => 'empresa-beta',
        'razon_social' => 'Empresa Beta S.A.C.',
        'ruc' => '20987654321',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $this->actingAs($admin)
        ->withSession(['empresa_actual_id' => $empresa->id])
        ->post(route('empresa.usuarios.invitar'), [
            'email' => 'hacker@ejemplo.com',
            'rol' => 'superadmin_global',
        ])
        ->assertSessionHasErrors(['rol']);
});

test('no se puede editar ni desactivar usuarios de tenants ajenos', function () {
    $empresa1 = Empresa::create([
        'name' => 'Empresa Uno',
        'slug' => 'empresa-uno',
        'razon_social' => 'Empresa Uno S.A.C.',
        'ruc' => '20111111111',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $empresa2 = Empresa::create([
        'name' => 'Empresa Dos',
        'slug' => 'empresa-dos',
        'razon_social' => 'Empresa Dos S.A.C.',
        'ruc' => '20222222222',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $admin1 = User::factory()->create();
    $empresa1->usuarios()->attach($admin1->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $usuario2 = User::factory()->create();
    $empresa2->usuarios()->attach($usuario2->id, ['rol' => RolEmpresa::GestorReclamaciones->value, 'activo' => true]);

    // Admin1 intenta desactivar usuario2 de Empresa2
    $this->actingAs($admin1)
        ->withSession(['empresa_actual_id' => $empresa1->id])
        ->patch(route('empresa.usuarios.toggle-estado', $usuario2))
        ->assertForbidden();
});

test('el usuario no puede desactivarse a si mismo', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Gamma',
        'slug' => 'empresa-gamma',
        'razon_social' => 'Empresa Gamma S.A.C.',
        'ruc' => '20333333333',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $this->actingAs($admin)
        ->withSession(['empresa_actual_id' => $empresa->id])
        ->patch(route('empresa.usuarios.toggle-estado', $admin))
        ->assertForbidden();
});

test('usuario multempresa puede alternar su tenant activo', function () {
    $empresaA = Empresa::create([
        'name' => 'Grupo A',
        'slug' => 'grupo-a',
        'razon_social' => 'Grupo A S.A.C.',
        'ruc' => '20444444444',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $empresaB = Empresa::create([
        'name' => 'Grupo B',
        'slug' => 'grupo-b',
        'razon_social' => 'Grupo B S.A.C.',
        'ruc' => '20555555555',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $usuario = User::factory()->create();
    $empresaA->usuarios()->attach($usuario->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);
    $empresaB->usuarios()->attach($usuario->id, ['rol' => RolEmpresa::Lector->value, 'activo' => true]);

    // Cambiar a Empresa B
    $this->actingAs($usuario)
        ->withSession(['empresa_actual_id' => $empresaA->id])
        ->post(route('empresa.cambiar'), [
            'empresa_id' => $empresaB->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('empresa_actual_id', $empresaB->id);
});

test('no se puede cambiar a una empresa donde el usuario no tiene membresia activa', function () {
    $empresaA = Empresa::create([
        'name' => 'Tienda A',
        'slug' => 'tienda-a',
        'razon_social' => 'Tienda A S.A.C.',
        'ruc' => '20666666666',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $empresaAjena = Empresa::create([
        'name' => 'Tienda Ajena',
        'slug' => 'tienda-ajena',
        'razon_social' => 'Tienda Ajena S.A.C.',
        'ruc' => '20777777777',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $usuario = User::factory()->create();
    $empresaA->usuarios()->attach($usuario->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    // Intenta cambiarse a Tienda Ajena sin tener membresía
    $this->actingAs($usuario)
        ->withSession(['empresa_actual_id' => $empresaA->id])
        ->post(route('empresa.cambiar'), [
            'empresa_id' => $empresaAjena->id,
        ])
        ->assertForbidden();
});

