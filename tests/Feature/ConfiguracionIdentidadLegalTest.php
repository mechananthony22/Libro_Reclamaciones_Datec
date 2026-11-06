<?php

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;

test('RF-008: valida RUC peruano de 11 dígitos y requisitos obligatorios', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Borrador',
        'slug' => 'empresa-borrador-1',
        'razon_social' => 'EMPRESA BORRADOR S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Javier Prado 100, Lima',
        'email' => 'admin@borrador.pe',
        'estado' => EstadoEmpresa::Borrador,
        'activo' => false,
    ]);

    $empresa->makeCurrent();

    // RUC con menos de 11 dígitos o formato erróneo debe fallar
    $response = $this->put('/configuracion/empresa', [
        'name' => 'Nuevo Nombre',
        'razon_social' => 'NUEVA RAZON S.A.C.',
        'ruc' => '12345', // Inválido
        'direccion' => 'Calle Falsa 123',
        'email' => 'nuevo@borrador.pe',
    ]);

    $response->assertSessionHasErrors(['ruc']);
});

test('RF-008: criterio de aceptación - sin mínimos legales completos no permite activar el formulario público', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Incompleta',
        'slug' => 'empresa-incompleta',
        'razon_social' => '', // Incompleto
        'ruc' => '20123456780',
        'direccion' => '', // Incompleto
        'email' => '',
        'estado' => EstadoEmpresa::Borrador,
        'activo' => false,
    ]);

    $empresa->makeCurrent();

    // Intentar activar sin mínimos legales completos
    $response = $this->put('/configuracion/empresa', [
        'name' => 'Empresa Incompleta',
        'razon_social' => '',
        'ruc' => '20123456780',
        'direccion' => '',
        'email' => 'test@test.pe',
        'solicitar_activacion' => true,
    ]);

    $response->assertSessionHasErrors();
    expect($empresa->fresh()->isActiva())->toBeFalse();
});

test('RF-008: con mínimos legales completos permite guardar y activar el Libro de Reclamaciones', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Lista',
        'slug' => 'empresa-lista',
        'razon_social' => 'EMPRESA LISTA S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Arequipa 500, Lima',
        'email' => 'contacto@lista.pe',
        'estado' => EstadoEmpresa::Borrador,
        'activo' => false,
    ]);

    $empresa->makeCurrent();

    $response = $this->put('/configuracion/empresa', [
        'name' => 'Empresa Lista Actualizada',
        'razon_social' => 'EMPRESA LISTA ACTUALIZADA S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Arequipa 500, Lima',
        'email' => 'contacto@lista.pe',
        'telefono' => '+51 987654321',
        'solicitar_activacion' => true,
    ]);

    $response->assertSessionHasNoErrors();
    expect($empresa->fresh()->isActiva())->toBeTrue()
        ->and($empresa->fresh()->estado)->toBe(EstadoEmpresa::Activa);
});
