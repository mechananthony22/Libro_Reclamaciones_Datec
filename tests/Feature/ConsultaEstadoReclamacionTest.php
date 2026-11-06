<?php

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->empresa = Empresa::create([
        'name' => 'Datec Peru SAC',
        'razon_social' => 'DATEC PERU S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Javier Prado Este 1234, Lima',
        'email' => 'contacto@datec.com.pe',
        'slug' => 'datec-peru',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
        'configuracion' => [],
    ]);

    $this->establecimiento = Establecimiento::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'EST-001',
        'nombre' => 'Sede Principal San Isidro',
        'direccion' => 'Av. Javier Prado Este 1234',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresa->id,
        'establecimiento_id' => $this->establecimiento->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Servicio,
        'monto_reclamado' => 150.00,
        'moneda' => 'PEN',
        'descripcion_bien' => 'Servicio de Internet Fibra Óptica',
        'detalle_problema' => 'Corte intempestivo del servicio durante tres días continuos.',
        'pedido_consumidor' => 'Devolución proporcional del importe cobrado.',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '47896541',
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Perez Gonzales',
            'email' => 'juan.perez@ejemplo.com',
            'telefono' => '987654321',
            'direccion' => 'Av. Principal 123',
            'es_menor_edad' => false,
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'DATEC PERU S.A.C.',
            'ruc' => '20123456789',
            'direccion' => 'Av. Javier Prado Este 1234, Lima',
        ],
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => now()->addDays(15),
        'token_consulta' => Str::random(40),
        'aviso_privacidad_version' => '1.0',
        'aviso_privacidad_aceptado_en' => now(),
        'aviso_privacidad_ip' => '127.0.0.1',
    ]);
});

test('un usuario puede acceder a la pagina de consulta de estado por slug de empresa', function () {
    $response = $this->get(route('libro.consulta', ['slug' => $this->empresa->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/ConsultaEstado')
        ->has('empresa')
        ->where('empresa.slug', $this->empresa->slug)
    );
});

test('el consumidor puede consultar su estado con numero de hoja y documento de identidad', function () {
    $response = $this->post(route('libro.consulta.post', ['slug' => $this->empresa->slug]), [
        'codigo' => '00000001-2026',
        'verificacion' => '47896541',
    ]);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/ConsultaEstado')
        ->has('expediente')
        ->where('expediente.numero_hoja', '00000001-2026')
        ->where('expediente.estado', 'pendiente')
        ->has('url_firmada')
    );
});

test('el consumidor puede consultar su estado con numero de hoja y correo electronico', function () {
    $response = $this->post(route('libro.consulta.post', ['slug' => $this->empresa->slug]), [
        'codigo' => '00000001-2026',
        'verificacion' => 'juan.perez@ejemplo.com',
    ]);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/ConsultaEstado')
        ->has('expediente')
        ->where('expediente.numero_hoja', '00000001-2026')
    );
});

test('el consumidor puede consultar usando el token unico de consulta con su factor de verificacion', function () {
    $response = $this->post(route('libro.consulta.post', ['slug' => $this->empresa->slug]), [
        'codigo' => $this->reclamacion->token_consulta,
        'verificacion' => '47896541',
    ]);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/ConsultaEstado')
        ->has('expediente')
        ->where('expediente.numero_hoja', '00000001-2026')
    );
});

test('no se permite el acceso si solo se adivina el numero de hoja con factor de verificacion incorrecto', function () {
    $response = $this->post(route('libro.consulta.post', ['slug' => $this->empresa->slug]), [
        'codigo' => '00000001-2026',
        'verificacion' => '99999999',
    ]);

    $response->assertSessionHasErrors(['verificacion']);
});

test('se enmascaran los datos personales del consumidor en el resultado para proteger la privacidad', function () {
    $response = $this->post(route('libro.consulta.post', ['slug' => $this->empresa->slug]), [
        'codigo' => '00000001-2026',
        'verificacion' => '47896541',
    ]);

    $response->assertOk();
    $propExpediente = $response->viewData('page')['props']['expediente'];

    // Validar que el documento y correo se retornan ofuscados
    expect($propExpediente['consumidor_documento'])->toContain('*')
        ->and($propExpediente['consumidor_email'])->toContain('*');
});

test('no se puede consultar una reclamacion que pertenezca a otra empresa', function () {
    $otraEmpresa = Empresa::create([
        'name' => 'Otra Empresa SAC',
        'razon_social' => 'OTRA EMPRESA S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Otra 456',
        'email' => 'contacto@otra.com',
        'slug' => 'otra-empresa',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
        'configuracion' => [],
    ]);

    $response = $this->post(route('libro.consulta.post', ['slug' => $otraEmpresa->slug]), [
        'codigo' => '00000001-2026',
        'verificacion' => '47896541',
    ]);

    $response->assertSessionHasErrors(['codigo']);
});

test('el acceso por enlace temporal firmado funciona mientras sea valido', function () {
    $urlFirmada = URL::temporarySignedRoute(
        'libro.consulta.firmada',
        now()->addMinutes(30),
        ['slug' => $this->empresa->slug, 'token' => $this->reclamacion->token_consulta]
    );

    $response = $this->get($urlFirmada);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/ConsultaEstado')
        ->has('expediente')
        ->where('expediente.numero_hoja', '00000001-2026')
    );
});

test('el acceso por enlace temporal firmado falla si la firma ha expirado o es invalida', function () {
    $urlExpirada = URL::temporarySignedRoute(
        'libro.consulta.firmada',
        now()->subMinute(),
        ['slug' => $this->empresa->slug, 'token' => $this->reclamacion->token_consulta]
    );

    $response = $this->get($urlExpirada);

    $response->assertForbidden();
});
