<?php

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;
use App\Models\Establecimiento;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('establecimientos')) {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id');
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->text('direccion');
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
});

test('RF-009: actualiza color primario accesible y valida formato HEX', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Visual',
        'slug' => 'empresa-visual',
        'razon_social' => 'EMPRESA VISUAL S.A.C.',
        'ruc' => '20123456790',
        'direccion' => 'Av. Larco 123',
        'color_primario' => '#2563EB',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresa->makeCurrent();

    // Color válido
    $response = $this->post('/configuracion/personalizacion', [
        'color_primario' => '#059669',
    ]);

    $response->assertSessionHasNoErrors();
    expect($empresa->fresh()->color_primario)->toBe('#059669');

    // Color inválido
    $responseInvalido = $this->post('/configuracion/personalizacion', [
        'color_primario' => 'color-no-hex',
    ]);

    $responseInvalido->assertSessionHasErrors(['color_primario']);
});

test('RF-009: eliminar logotipo restaura la presentación por defecto', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Logo',
        'slug' => 'empresa-logo',
        'razon_social' => 'EMPRESA LOGO S.A.C.',
        'ruc' => '20123456791',
        'direccion' => 'Av. Pardo 456',
        'logo_path' => '/storage/logos/dummy.png',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresa->makeCurrent();

    $response = $this->delete('/configuracion/personalizacion/logo');

    $response->assertSessionHasNoErrors();
    expect($empresa->fresh()->logo_path)->toBeNull();
});

test('RF-010: crear y gestionar sedes asociadas al tenant con sede principal única', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Sedes',
        'slug' => 'empresa-sedes',
        'razon_social' => 'EMPRESA SEDES S.A.C.',
        'ruc' => '20123456792',
        'direccion' => 'Av. Central 789',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresa->makeCurrent();

    // Crear primera sede (debe quedar como principal)
    $response1 = $this->post('/configuracion/establecimientos', [
        'codigo' => 'SEDE-01',
        'nombre' => 'Sede Miraflores',
        'direccion' => 'Av. Larco 100',
        'es_principal' => true,
    ]);
    $response1->assertSessionHasNoErrors();

    // Crear segunda sede como principal (debe quitar el flag a la primera)
    $response2 = $this->post('/configuracion/establecimientos', [
        'codigo' => 'SEDE-02',
        'nombre' => 'Sede San Isidro',
        'direccion' => 'Av. Conquistadores 200',
        'es_principal' => true,
    ]);
    $response2->assertSessionHasNoErrors();

    $sede1 = Establecimiento::where('codigo', 'SEDE-01')->first();
    $sede2 = Establecimiento::where('codigo', 'SEDE-02')->first();

    expect($sede1->es_principal)->toBeFalse()
        ->and($sede2->es_principal)->toBeTrue();
});

test('RF-010: criterio de aceptación - API pública exige selección cuando hay más de una sede activa', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Multisede',
        'slug' => 'empresa-multisede',
        'razon_social' => 'EMPRESA MULTISEDE S.A.C.',
        'ruc' => '20123456793',
        'direccion' => 'Calle Principal 100',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresa->makeCurrent();

    Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-A',
        'nombre' => 'Local Centro',
        'direccion' => 'Jr. de la Unión 500',
        'es_principal' => true,
        'activo' => true,
    ]);

    Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-B',
        'nombre' => 'Local Sur',
        'direccion' => 'Av. Primavera 600',
        'es_principal' => false,
        'activo' => true,
    ]);

    $response = $this->get("/l/{$empresa->slug}/establecimientos");

    $response->assertStatus(200);
    $response->assertJson([
        'requiere_seleccion' => true,
        'total' => 2,
    ]);
});
