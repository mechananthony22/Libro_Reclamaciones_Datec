<?php

use App\Actions\Reclamaciones\RegistrarReclamacionAction;
use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\PlataformaAlerta;
use App\Models\Reclamacion;
use App\Models\User;
use App\Services\PlataformaSaludService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

    $this->empresa = Empresa::create([
        'name' => 'Farmacéutica Nacional SAC',
        'razon_social' => 'FARMACÉUTICA NACIONAL S.A.C.',
        'ruc' => '20556677889',
        'direccion' => 'Av. Javier Prado Este 2500, San Borja, Lima',
        'email' => 'reclamos@farmaceutica.pe',
        'telefono' => '01-3344556',
        'slug' => 'farmaceutica-nacional',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->establecimiento = Establecimiento::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'SEDE-01',
        'nombre' => 'Sede San Borja',
        'direccion' => 'Av. Javier Prado Este 2500',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Operaciones',
        'email' => 'operaciones@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);
});

// ==========================================
// PRUEBAS DE DISPONIBILIDAD Y ERRORES (RF-044)
// ==========================================

test('el servicio de salud evalua correctamente los recursos del sistema y métricas operativas', function () {
    $saludService = app(PlataformaSaludService::class);
    $recursos = $saludService->obtenerEstadoRecursos();

    expect($recursos)->toHaveKeys(['base_datos', 'almacenamiento', 'conteos', 'servidor'])
        ->and($recursos['base_datos']['estado'])->toBe('conectado')
        ->and($recursos['conteos']['empresas_total'])->toBeGreaterThanOrEqual(1);
});

test('se genera una alerta operativa cuando las métricas superan los umbrales definidos para Datec', function () {
    $saludService = app(PlataformaSaludService::class);

    // Simular que la empresa entra en contingencia para superar el umbral de contingencias (> 0)
    $this->empresa->update(['en_contingencia' => true]);

    $diagnostico = $saludService->evaluarUmbralesYAlertar();

    expect($diagnostico['estado_global'])->toBeIn(['advertencia', 'critico'])
        ->and(count($diagnostico['alertas_activas']))->toBeGreaterThanOrEqual(1);

    $alerta = PlataformaAlerta::where('tipo', 'contingencia_activa')->where('estado', 'activa')->first();
    expect($alerta)->not->toBeNull()
        ->and($alerta->notificado_datec_en)->not->toBeNull();

    // Cuando se restablece la contingencia, la alerta se resuelve automáticamente
    $this->empresa->update(['en_contingencia' => false]);
    $diagnosticoNormalizado = $saludService->evaluarUmbralesYAlertar();

    expect(PlataformaAlerta::where('tipo', 'contingencia_activa')->where('estado', 'activa')->exists())->toBeFalse();
});

test('comando artisan plataforma:verificar-salud evalua umbrales y reporta estado con codigo de exito', function () {
    $this->artisan('plataforma:verificar-salud')
        ->expectsOutputToContain('ESTADO GLOBAL DE PLATAFORMA')
        ->assertSuccessful();
});

test('superadministrador puede consultar panel de monitoreo y resolver alertas manualmente', function () {
    $this->empresa->update(['en_contingencia' => true]);

    $response = $this->actingAs($this->superadmin)
        ->get('/admin/monitoreo');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Monitoreo/Index')
        ->has('salud')
        ->has('salud.alertas_activas')
    );

    $alerta = PlataformaAlerta::where('tipo', 'contingencia_activa')->first();
    expect($alerta)->not->toBeNull();

    // Superadmin resuelve manualmente la alerta
    $resolverResponse = $this->actingAs($this->superadmin)
        ->post("/admin/monitoreo/alertas/{$alerta->id}/resolver");

    $resolverResponse->assertRedirect();
    expect($alerta->fresh()->estado)->toBe('resuelta')
        ->and($alerta->fresh()->resuelta_en)->not->toBeNull();
});

// ==========================================
// PRUEBAS DE INTEGRACIÓN API FUTURA (RF-045)
// ==========================================

test('la API pública se encuentra deshabilitada por defecto en la versión MVP respondiendo 503 controlado', function () {
    // Verificar que por defecto en configuración la API está inactiva
    expect(config('services.api.public_enabled', false))->toBeFalse();

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'tipo' => 'reclamo',
    ]);

    $response->assertStatus(503)
        ->assertJson([
            'error' => 'api_no_disponible_en_mvp',
            'fase' => 'F2',
        ]);
});

test('el caso de uso RegistrarReclamacionAction es desacoplado y reutilizable fuera de Inertia', function () {
    $action = app(RegistrarReclamacionAction::class);

    $datos = [
        'establecimiento_id' => $this->establecimiento->id,
        'tipo_documento' => 'dni',
        'numero_documento' => '44332211',
        'nombres' => 'Patricia',
        'apellidos' => 'Flores Ríos',
        'email' => 'patricia.flores@gmail.com',
        'telefono' => '987112233',
        'direccion' => 'Calle Los Cedros 123',
        'tipo' => 'reclamo',
        'tipo_bien' => 'producto',
        'descripcion_bien' => 'Medicamento recetado',
        'monto_reclamado' => 45.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'El empaque del producto se encontraba adulterado.',
        'pedido_consumidor' => 'Reposición de medicina en buen estado.',
    ];

    $reclamacion = $action->ejecutar($this->empresa, $datos);

    expect($reclamacion)->toBeInstanceOf(Reclamacion::class)
        ->and($reclamacion->numero_hoja)->not->toBeEmpty()
        ->and($reclamacion->empresa_id)->toBe($this->empresa->id)
        ->and($reclamacion->datos_consumidor['numero_documento'])->toBe('44332211');
});

test('cuando la API se habilita para la fase F2 procesa el registro y retorna el recurso REST con código 201', function () {
    // Habilitar temporalmente la API simulando el flag de expansión F2
    Config::set('services.api.public_enabled', true);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'establecimiento_id' => $this->establecimiento->id,
        'tipo_documento' => 'dni',
        'numero_documento' => '77665544',
        'nombres' => 'Manuel',
        'apellidos' => 'Torres Gálvez',
        'email' => 'manuel.torres@gmail.com',
        'telefono' => '998877665',
        'direccion' => 'Av. Javier Prado 100',
        'tipo' => 'queja',
        'tipo_bien' => 'servicio',
        'descripcion_bien' => 'Atención farmacéutica en caja',
        'detalle_problema' => 'Mala atención del personal encargado.',
        'pedido_consumidor' => 'Capacitación en trato al usuario.',
        'aviso_privacidad' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'numero_hoja',
                'tipo',
                'tipo_bien',
                'estado',
                'consumidor' => [
                    'tipo_documento',
                    'numero_documento',
                    'nombres',
                ],
            ],
        ]);
    expect($response->json('data.token_consulta'))->toBeNull();
});

test('cuando la API se habilita permite consultar el estado del expediente mediante caso de uso desacoplado', function () {
    Config::set('services.api.public_enabled', true);

    // Crear un expediente previo
    $reclamacion = app(RegistrarReclamacionAction::class)->ejecutar($this->empresa, [
        'establecimiento_id' => $this->establecimiento->id,
        'tipo_documento' => 'dni',
        'numero_documento' => '12345678',
        'nombres' => 'Raúl',
        'apellidos' => 'Mendoza',
        'email' => 'raul@gmail.com',
        'telefono' => '999111222',
        'direccion' => 'Calle Uno 123',
        'tipo' => 'reclamo',
        'tipo_bien' => 'producto',
        'descripcion_bien' => 'Vitaminas',
        'detalle_problema' => 'Producto vencido',
        'pedido_consumidor' => 'Devolución de dinero',
    ]);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones/consultar', [
        'tipo_documento' => 'dni',
        'numero_documento' => '12345678',
        'numero_reclamacion' => $reclamacion->numero_hoja,
    ]);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'numero_hoja' => $reclamacion->numero_hoja,
                'tipo' => 'reclamo',
                'estado' => 'pendiente',
            ],
        ]);
});

test('la API rechaza solicitudes con HTTP 503 si la empresa se encuentra en modo de contingencia', function () {
    Config::set('services.api.public_enabled', true);
    $this->empresa->update(['en_contingencia' => true, 'contingencia_mensaje' => 'API en mantenimiento']);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'tipo' => 'reclamo',
    ]);

    $response->assertStatus(503)
        ->assertJson([
            'error' => 'portal_en_contingencia',
        ]);
});

test('la API rechaza registro si no se acepta el aviso de privacidad', function () {
    Config::set('services.api.public_enabled', true);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'establecimiento_id' => $this->establecimiento->id,
        'tipo_documento' => 'dni',
        'numero_documento' => '77665544',
        'nombres' => 'Manuel',
        'email' => 'manuel.torres@gmail.com',
        'telefono' => '998877665',
        'direccion' => 'Av. Javier Prado 100',
        'tipo' => 'reclamo',
        'tipo_bien' => 'servicio',
        'descripcion_bien' => 'Atención',
        'detalle_problema' => 'Problema reportado con suficiente detalle.',
        'pedido_consumidor' => 'Solución',
        // Falta aviso_privacidad
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['aviso_privacidad']);
});

test('la API rechaza establecimiento perteneciente a otro tenant', function () {
    Config::set('services.api.public_enabled', true);

    $otraEmpresa = Empresa::create([
        'name' => 'Otra Empresa SAC',
        'razon_social' => 'OTRA EMPRESA S.A.C.',
        'ruc' => '20999999999',
        'slug' => 'otra-empresa',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $sedeAjena = Establecimiento::create([
        'empresa_id' => $otraEmpresa->id,
        'codigo' => 'OTRA-01',
        'nombre' => 'Sede Ajena',
        'direccion' => 'Calle Ajena 123',
        'activo' => true,
    ]);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'establecimiento_id' => $sedeAjena->id, // Pertenece a otra empresa
        'tipo_documento' => 'dni',
        'numero_documento' => '77665544',
        'nombres' => 'Manuel',
        'email' => 'manuel@gmail.com',
        'telefono' => '998877665',
        'direccion' => 'Av. Javier Prado 100',
        'tipo' => 'reclamo',
        'tipo_bien' => 'servicio',
        'descripcion_bien' => 'Atención',
        'detalle_problema' => 'Problema reportado con suficiente detalle.',
        'pedido_consumidor' => 'Solución',
        'aviso_privacidad' => true,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['establecimiento_id']);
});

test('la API rechaza solicitudes si la empresa se encuentra inactiva', function () {
    Config::set('services.api.public_enabled', true);
    $this->empresa->update(['activo' => false]);

    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'tipo' => 'reclamo',
    ]);

    $response->assertStatus(403);

    $responseEstablecimientos = $this->getJson('/api/v1/farmaceutica-nacional/establecimientos');
    $responseEstablecimientos->assertStatus(403);
});

test('la API exige API Key si se encuentra configurada en el sistema', function () {
    Config::set('services.api.public_enabled', true);
    Config::set('services.api.key', 'secret-api-key-12345');

    // Sin cabecera
    $response = $this->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
        'tipo' => 'reclamo',
    ]);
    $response->assertStatus(401);

    // Con cabecera incorrecta
    $responseInvalida = $this->withHeaders(['X-API-Key' => 'clave-erronea'])
        ->postJson('/api/v1/farmaceutica-nacional/reclamaciones', [
            'tipo' => 'reclamo',
        ]);
    $responseInvalida->assertStatus(401);

    // Con cabecera correcta
    $responseValida = $this->withHeaders(['X-API-Key' => 'secret-api-key-12345'])
        ->getJson('/api/v1/farmaceutica-nacional/establecimientos');
    $responseValida->assertOk();
});
