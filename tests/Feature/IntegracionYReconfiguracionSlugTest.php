<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\EmpresaSlugHistorial;
use App\Models\Establecimiento;
use App\Models\User;
use App\Services\SlugReconfigurationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

    $this->empresaA = Empresa::create([
        'name' => 'Librería Central SAC',
        'razon_social' => 'LIBRERÍA CENTRAL S.A.C.',
        'ruc' => '20101010101',
        'direccion' => 'Av. Larco 789, Miraflores, Lima',
        'email' => 'contacto@libreriacentral.pe',
        'telefono' => '01-2233445',
        'slug' => 'libreria-central',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->establecimientoA = Establecimiento::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'EST-01',
        'nombre' => 'Sede Miraflores',
        'direccion' => 'Av. Larco 789',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Farmacia del Sol SAC',
        'razon_social' => 'FARMACIA DEL SOL S.A.C.',
        'ruc' => '20202020202',
        'direccion' => 'Av. Arequipa 1234, Lima',
        'email' => 'contacto@farmaciadelsol.pe',
        'slug' => 'farmacia-sol',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->adminA = User::create([
        'name' => 'Admin Librería',
        'email' => 'admin@libreriacentral.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);
    $this->adminA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->adminB = User::create([
        'name' => 'Admin Farmacia',
        'email' => 'admin@farmaciadelsol.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);
    $this->adminB->empresas()->attach($this->empresaB->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);
});

test('administrador de empresa puede acceder a la sección de integración y ver sus enlaces y fragmentos HTML', function () {
    $response = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->get('/configuracion/integracion');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Integracion/Index')
        ->has('empresa')
        ->has('urls')
        ->has('snippets')
        ->where('urls.libro', url('/l/libreria-central'))
        ->where('urls.consulta', url('/l/libreria-central/consultar'))
        ->has('snippets.boton_estandar')
        ->has('snippets.enlace_texto')
    );
});

test('administrador puede reconfigurar su slug y el slug previo queda registrado en el historial', function () {
    $response = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->post('/configuracion/integracion/slug', [
            'slug' => 'libreria-central-peru',
            'motivo' => 'Rebranding comercial para abarcar cobertura nacional',
        ]);

    $response->assertRedirect();
    $this->empresaA->refresh();

    expect($this->empresaA->slug)->toBe('libreria-central-peru');

    // Criterio de aceptación: slug antiguo preservado en historial
    $historial = EmpresaSlugHistorial::where('empresa_id', $this->empresaA->id)->first();
    expect($historial)->not->toBeNull()
        ->and($historial->slug_antiguo)->toBe('libreria-central')
        ->and($historial->motivo)->toContain('Rebranding comercial');

    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresaA->id,
        'accion' => 'reconfigurar_slug',
    ]);
});

test('visita al slug antiguo redirige de forma permanente HTTP 301 al nuevo slug sin romper enlaces', function () {
    // Cambiar slug
    app(SlugReconfigurationService::class)->cambiarSlug(
        $this->empresaA,
        'libreria-central-oficial',
        'Cambio formal',
        $this->adminA
    );

    // Acceso a la raíz del libro con slug antiguo
    $response = $this->get('/l/libreria-central');
    $response->assertStatus(301);
    $response->assertRedirect('/l/libreria-central-oficial');

    // Acceso a la consulta de estado con slug antiguo
    $responseConsulta = $this->get('/l/libreria-central/consultar');
    $responseConsulta->assertStatus(301);
    $responseConsulta->assertRedirect('/l/libreria-central-oficial/consultar');
});

test('otra empresa no puede solicitar ni colisionar con un slug histórico de otro cliente', function () {
    // Empresa A cambia de 'libreria-central' a 'libreria-central-nueva'
    app(SlugReconfigurationService::class)->cambiarSlug(
        $this->empresaA,
        'libreria-central-nueva',
        'Cambio',
        $this->adminA
    );

    // Empresa B intenta registrar 'libreria-central' (antiguo slug de A)
    $response = $this->actingAs($this->adminB)
        ->withSession(['current_empresa_id' => $this->empresaB->id])
        ->post('/configuracion/integracion/slug', [
            'slug' => 'libreria-central',
        ]);

    $response->assertSessionHasErrors('slug');
    expect($this->empresaB->fresh()->slug)->toBe('farmacia-sol');
});

test('no se permite reconfigurar a palabras reservadas del sistema o formato inválido', function () {
    $response = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->post('/configuracion/integracion/slug', [
            'slug' => 'admin',
        ]);

    $response->assertSessionHasErrors('slug');
    expect($this->empresaA->fresh()->slug)->toBe('libreria-central');

    // Formato con caracteres inválidos
    $response2 = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->post('/configuracion/integracion/slug', [
            'slug' => 'libreria_invalida!',
        ]);

    $response2->assertSessionHasErrors('slug');
});

test('endpoint de verificación asíncrona de slug valida disponibilidad en tiempo real', function () {
    // Slug disponible
    $responseDisponible = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->postJson('/configuracion/integracion/slug/verificar', [
            'slug' => 'nuevo-slug-disponible',
        ]);

    $responseDisponible->assertOk()
        ->assertJson([
            'valido' => true,
            'disponible' => true,
        ]);

    // Slug de otra empresa
    $responseOcupado = $this->actingAs($this->adminA)
        ->withSession(['current_empresa_id' => $this->empresaA->id])
        ->postJson('/configuracion/integracion/slug/verificar', [
            'slug' => 'farmacia-sol',
        ]);

    $responseOcupado->assertOk()
        ->assertJson([
            'valido' => true,
            'disponible' => false,
        ]);
});
