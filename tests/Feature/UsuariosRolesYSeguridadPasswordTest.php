<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('P2-14: aceptar invitacion exige contrasena de minimo 10 caracteres', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Clave Minima',
        'slug' => 'empresa-clave-minima',
        'razon_social' => 'Empresa Clave Minima S.A.C.',
        'ruc' => '20123999991',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $invitacion = InvitacionEmpresa::generarPara(
        empresa: $empresa,
        email: 'nuevo.usuario@ejemplo.pe',
        nombre: 'Nuevo Usuario',
        rol: RolEmpresa::GestorReclamaciones,
        horasExpiracion: 72
    );

    // 1. Password con 9 caracteres (debe fallar la validación)
    $responseFalla = $this->post(route('invitacion.store', ['token' => $invitacion->token]), [
        'name' => 'Nuevo Usuario',
        'password' => 'Abc12345!',
        'password_confirmation' => 'Abc12345!',
    ]);
    $responseFalla->assertSessionHasErrors(['password']);

    // 2. Password con 10 caracteres (pasa validación de longitud)
    $responseExito = $this->post(route('invitacion.store', ['token' => $invitacion->token]), [
        'name' => 'Nuevo Usuario',
        'password' => 'Abc123456!',
        'password_confirmation' => 'Abc123456!',
    ]);
    $responseExito->assertSessionDoesntHaveErrors(['password']);
    $responseExito->assertRedirect(route('login'));
});

test('P2-14: restablecer contrasena exige minimo 10 caracteres', function () {
    $user = User::factory()->create([
        'email' => 'reset.min10@ejemplo.pe',
        'password' => Hash::make('password123'),
    ]);

    $token = Str::random(60);
    DB::table('password_reset_tokens')->insert([
        'email' => 'reset.min10@ejemplo.pe',
        'token' => Hash::make('123456'),
        'created_at' => now(),
    ]);

    // 1. Clave de 9 caracteres falla
    $responseFalla = $this->post(route('password.restablecer'), [
        'email' => 'reset.min10@ejemplo.pe',
        'codigo' => '123456',
        'password' => '123456789',
        'password_confirmation' => '123456789',
    ]);
    $responseFalla->assertSessionHasErrors(['password']);

    // 2. Clave de 10 caracteres pasa
    $responseExito = $this->post(route('password.restablecer'), [
        'email' => 'reset.min10@ejemplo.pe',
        'codigo' => '123456',
        'password' => '1234567890',
        'password_confirmation' => '1234567890',
    ]);
    $responseExito->assertSessionDoesntHaveErrors(['password']);
    $responseExito->assertRedirect(route('login'));
});

test('P2-13: usuario sin rol admin_empresa recibe 403 en todas las rutas de gestion de usuarios y auditoria', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Seguridad Roles',
        'slug' => 'empresa-seguridad-roles',
        'razon_social' => 'Empresa Seguridad Roles S.A.C.',
        'ruc' => '20123999992',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $operador = User::factory()->create();
    $empresa->usuarios()->attach($operador->id, ['rol' => RolEmpresa::GestorReclamaciones->value, 'activo' => true]);

    // 1. Acceso a index de usuarios
    $this->actingAs($operador)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->get(route('empresa.usuarios.index'))
        ->assertForbidden();

    // 2. Acceso a invitar
    $this->actingAs($operador)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->post(route('empresa.usuarios.invitar'), [
            'email' => 'intruso@empresa.pe',
            'rol' => RolEmpresa::LectorEmpresa->value,
        ])
        ->assertForbidden();

    // 3. Acceso a auditoria
    $this->actingAs($operador)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->get(route('empresa.auditoria.index'))
        ->assertForbidden();

    $this->actingAs($operador)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->get(route('empresa.auditoria.exportar'))
        ->assertForbidden();
});

test('P2-13: no es posible invitar a un segundo administrador de empresa que desplace al titular', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Titular Protegido',
        'slug' => 'empresa-titular-protegido',
        'razon_social' => 'Empresa Titular Protegido S.A.C.',
        'ruc' => '20123999993',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $adminTitular = User::factory()->create(['email' => 'titular@empresa.pe']);
    $empresa->usuarios()->attach($adminTitular->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $this->actingAs($adminTitular)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->post(route('empresa.usuarios.invitar'), [
            'email' => 'segundo.admin@empresa.pe',
            'rol' => RolEmpresa::AdminEmpresa->value,
        ])
        ->assertSessionHasErrors(['rol']);

    $this->assertDatabaseMissing('invitaciones_empresa', [
        'email' => 'segundo.admin@empresa.pe',
    ]);
});

test('P2-13: no es posible degradar ni desvincular al administrador titular de la empresa', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Inviolabilidad Titular',
        'slug' => 'empresa-inviolabilidad-titular',
        'razon_social' => 'Empresa Inviolabilidad Titular S.A.C.',
        'ruc' => '20123999994',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $adminTitular = User::factory()->create();
    $empresa->usuarios()->attach($adminTitular->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    // Intentar degradar a Lector
    $this->actingAs($adminTitular)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->put(route('empresa.usuarios.rol', $adminTitular), [
            'rol' => RolEmpresa::LectorEmpresa->value,
        ])
        ->assertSessionHasErrors(['rol']);

    // Intentar desvincular al titular
    $this->actingAs($adminTitular)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->delete(route('empresa.usuarios.desvincular', $adminTitular))
        ->assertSessionHasErrors(['error']);
});
