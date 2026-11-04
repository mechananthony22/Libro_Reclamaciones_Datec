<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;

test('el formulario publico responde sin necesidad de iniciar sesion e identifica al proveedor', function () {
    $empresa = Empresa::create([
        'name' => 'Tienda Digital Demo',
        'slug' => 'tienda-digital-demo',
        'razon_social' => 'Tienda Digital Demo S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Los Libertadores 456, San Isidro, Lima',
        'email' => 'reclamos@tiendademo.pe',
        'telefono' => '01-4455667',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $response = $this->get(route('libro.publico', ['slug' => $empresa->slug]));

    $response->assertOk();
    $response->assertSee('Tienda Digital Demo');
    $response->assertSee('20123456789');
    $response->assertSee('Av. Los Libertadores 456, San Isidro, Lima');
});

test('valida los datos minimos del consumidor y el formato del documento', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Validador',
        'slug' => 'comercio-validador',
        'razon_social' => 'Comercio Validador S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Pardo 123, Miraflores',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    // DNI inválido (debe tener 8 dígitos)
    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '123', // Incompleto
        'nombres' => 'Juan Carlos',
        'apellidos' => 'Perez Gonzales',
        'email' => 'juan@ejemplo.com',
        'telefono' => '987654321',
        'direccion' => 'Calle Las Begonias 123',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Televisor Smart 55 pulgadas',
    ]);

    $response->assertSessionHasErrors(['numero_documento']);
});

test('exige datos del representante legal cuando el consumidor es menor de edad', function () {
    $empresa = Empresa::create([
        'name' => 'Jugueteria Central',
        'slug' => 'jugueteria-central',
        'razon_social' => 'Jugueteria Central S.A.C.',
        'ruc' => '20456123789',
        'direccion' => 'Jr. de la Union 789, Lima',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    // Menor de edad sin datos del apoderado
    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '78945612',
        'nombres' => 'Mateo',
        'apellidos' => 'Flores',
        'email' => 'apoderado@ejemplo.com',
        'telefono' => '955443322',
        'direccion' => 'Calle Los Pinos 450',
        'es_menor_edad' => true,
        'apoderado_nombre' => '', // Falta
        'apoderado_tipo_documento' => null,
        'apoderado_numero_documento' => '',
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Bicicleta montañera aro 24',
    ]);

    $response->assertSessionHasErrors(['apoderado_nombre', 'apoderado_tipo_documento', 'apoderado_numero_documento']);
});

test('registra correctamente los datos del consumidor y la identificacion del bien o servicio', function () {
    $empresa = Empresa::create([
        'name' => 'Tienda Tech Soluciones',
        'slug' => 'tienda-tech-soluciones',
        'razon_social' => 'Tech Soluciones S.A.C.',
        'ruc' => '20789456123',
        'direccion' => 'Av. Javier Prado Este 2500, San Borja',
        'email' => 'contacto@techsoluciones.pe',
        'telefono' => '01-2244668',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Estandar,
        'activo' => true,
    ]);

    $establecimiento = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'EST-01',
        'nombre' => 'Sede Central San Borja',
        'direccion' => 'Av. Javier Prado Este 2500',
        'es_principal' => true,
        'activo' => true,
    ]);

    $payload = [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '44889900',
        'nombres' => 'Ana Lucia',
        'apellidos' => 'Mendoza Ramos',
        'email' => 'anamendoza@ejemplo.pe',
        'telefono' => '998877665',
        'direccion' => 'Calle Bellavista 320, Dpto 401',
        'departamento' => 'Lima',
        'provincia' => 'Lima',
        'distrito' => 'Miraflores',
        'es_menor_edad' => false,

        'establecimiento_id' => $establecimiento->id,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Servicio de soporte tecnico remoto y mantenimiento preventivo de servidores.',
        'monto_reclamado' => 450.50,
        'moneda' => 'PEN',
        'numero_comprobante' => 'F001-000341',
        'fecha_contratacion' => '2026-10-15',
    ];

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payload);

    $response->assertRedirect(route('libro.publico', ['slug' => $empresa->slug]));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reclamaciones', [
        'empresa_id' => $empresa->id,
        'establecimiento_id' => $establecimiento->id,
        'tipo_bien' => TipoBien::Servicio->value,
        'monto_reclamado' => 450.50,
        'moneda' => 'PEN',
        'numero_comprobante' => 'F001-000341',
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();
    expect($reclamacion->datos_consumidor['nombres'])->toBe('Ana Lucia');
    expect($reclamacion->datos_consumidor['email'])->toBe('anamendoza@ejemplo.pe');
    expect($reclamacion->datos_legales_snapshot['razon_social'])->toBe('Tech Soluciones S.A.C.');
    expect($reclamacion->datos_legales_snapshot['ruc'])->toBe('20789456123');
});

