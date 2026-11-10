<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\ContingenciaIncidente;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Models\User;
use App\Services\ContingenciaService;
use App\Services\SalidaClienteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ZipArchive;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

    $this->empresa = Empresa::create([
        'name' => 'Comercial Andina SAC',
        'razon_social' => 'COMERCIAL ANDINA S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Los Próceres 450, Santiago de Surco, Lima',
        'email' => 'atencion@andina.pe',
        'telefono' => '01-4455667',
        'slug' => 'comercial-andina',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->establecimiento = Establecimiento::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'EST-01',
        'nombre' => 'Sede Principal Surco',
        'direccion' => 'Av. Los Próceres 450',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->adminUsuario = User::create([
        'name' => 'Carlos Administrador',
        'email' => 'admin@andina.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);
    $this->adminUsuario->empresas()->attach($this->empresa->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Datec',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);
});

test('administrador de empresa puede activar el plan de contingencia con mensaje y canales alternativos', function () {
    $response = $this->actingAs($this->adminUsuario)
        ->withSession(['current_empresa_id' => $this->empresa->id])
        ->post('/configuracion/contingencia/activar', [
            'titulo' => 'Mantenimiento Preventivo de Servidores',
            'tipo' => 'mantenimiento',
            'mensaje' => 'Estamos realizando labores de mantenimiento programado. Use el canal de correo.',
            'email' => 'emergencias@andina.pe',
            'telefono' => '999888777',
            'direccion_fisica' => 'Av. Los Próceres 450, Surco',
            'horario' => 'Lunes a Viernes 08:00 a 17:00',
        ]);

    $response->assertRedirect();
    $this->empresa->refresh();

    expect($this->empresa->en_contingencia)->toBeTrue()
        ->and($this->empresa->contingencia_mensaje)->toContain('mantenimiento programado')
        ->and($this->empresa->contingencia_canal_alternativo['email'])->toBe('emergencias@andina.pe');

    $this->assertDatabaseHas('contingencia_incidentes', [
        'empresa_id' => $this->empresa->id,
        'tipo' => 'mantenimiento',
        'estado' => 'activo',
    ]);

    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'activar_contingencia',
    ]);
});

test('visita pública al portal en contingencia responde HTTP 503 con aviso y canales alternativos', function () {
    app(ContingenciaService::class)->activarContingencia($this->empresa, [
        'mensaje' => 'Plataforma en contingencia operativa.',
        'canales' => [
            'email' => 'soporte@andina.pe',
            'telefono' => '01-2223344',
            'direccion_fisica' => 'Av. Principal 123',
            'horario' => '9am - 6pm',
        ],
    ]);

    $response = $this->get('/l/comercial-andina');

    $response->assertStatus(503);
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/Contingencia')
        ->has('empresa')
        ->has('contingencia')
        ->where('contingencia.mensaje', 'Plataforma en contingencia operativa.')
        ->where('contingencia.canales.email', 'soporte@andina.pe')
    );
});

test('intento de registrar reclamo durante contingencia es rechazado con 503 y no genera correlativo ni falsos registros', function () {
    app(ContingenciaService::class)->activarContingencia($this->empresa, [
        'mensaje' => 'Canal digital cerrado por contingencia.',
    ]);

    $tokenSeguridad = encrypt(now()->subSeconds(10)->timestamp);

    $conteoPrevioReclamaciones = Reclamacion::count();

    $response = $this->post('/l/comercial-andina/registrar', [
        'establecimiento_id' => $this->establecimiento->id,
        'tipo_documento' => 'dni',
        'numero_documento' => '45678912',
        'nombres' => 'Juan',
        'apellidos' => 'Pérez Quispe',
        'email' => 'juan.perez@gmail.com',
        'telefono' => '987654321',
        'direccion' => 'Calle Las Camelias 123',
        'es_menor_edad' => false,
        'tipo_bien' => 'producto',
        'monto_reclamado' => 150.00,
        'descripcion_producto' => 'Audífonos inalámbricos',
        'tipo_reclamacion' => 'reclamo',
        'detalle_reclamacion' => 'El producto dejó de funcionar al segundo día.',
        'pedido_consumidor' => 'Cambio de producto o devolución de dinero.',
        'acepta_politica_privacidad' => true,
        'declara_veracidad' => true,
        'token_seguridad' => $tokenSeguridad,
    ]);

    $response->assertStatus(503);

    // Criterio de aceptación: no se generan registros ni números correlativos falsos
    expect(Reclamacion::count())->toBe($conteoPrevioReclamaciones);
});

test('la empresa puede ejecutar un ensayo preventivo de recuperación (DR) documentado en bitácora sin generar reclamos falsos', function () {
    $conteoPrevio = Reclamacion::count();

    $response = $this->actingAs($this->adminUsuario)
        ->withSession(['current_empresa_id' => $this->empresa->id])
        ->post('/configuracion/contingencia/ensayo', [
            'titulo' => 'Simulacro Semestral de Recuperación ante Desastres',
            'descripcion' => 'Verificación integral de continuidad de canales alternativos.',
            'resultado' => 'Ensayo concluido exitosamente sin discrepancias ni expedientes falsos emitidos.',
        ]);

    $response->assertRedirect();

    expect(Reclamacion::count())->toBe($conteoPrevio);

    $incidente = ContingenciaIncidente::where('empresa_id', $this->empresa->id)
        ->where('tipo', 'ensayo_recuperacion')
        ->first();

    expect($incidente)->not->toBeNull()
        ->and($incidente->estado)->toBe('ensayo_completado')
        ->and($incidente->resultado_ensayo)->toContain('sin discrepancias');

    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'ensayo_recuperacion',
    ]);
});

test('desactivar contingencia restablece la operación ordinaria del portal público', function () {
    app(ContingenciaService::class)->activarContingencia($this->empresa, [
        'mensaje' => 'Cierre provisional.',
    ]);

    expect($this->empresa->fresh()->estaEnContingencia())->toBeTrue();

    $response = $this->actingAs($this->adminUsuario)
        ->withSession(['current_empresa_id' => $this->empresa->id])
        ->post('/configuracion/contingencia/desactivar', [
            'resolucion' => 'Incidente resuelto y servicio normalizado.',
        ]);

    $response->assertRedirect();
    expect($this->empresa->fresh()->estaEnContingencia())->toBeFalse();

    $publicResponse = $this->get('/l/comercial-andina');
    $publicResponse->assertOk();
    $publicResponse->assertInertia(fn ($page) => $page->component('Publico/LibroReclamaciones'));
});

test('superadministrador puede gestionar contingencias desde el panel central', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/admin/contingencia');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Contingencia/Index')
        ->has('metricas')
        ->has('empresas')
        ->has('incidentes')
    );

    // Superadmin activa contingencia para una empresa
    $activaResponse = $this->actingAs($this->superadmin)
        ->post("/admin/contingencia/{$this->empresa->id}/activar", [
            'titulo' => 'Mantenimiento General de Base de Datos',
            'mensaje' => 'Mantenimiento preventivo ordenado por infraestructura central.',
        ]);

    $activaResponse->assertRedirect();
    expect($this->empresa->fresh()->estaEnContingencia())->toBeTrue();

    // Superadmin desactiva contingencia
    $desactivaResponse = $this->actingAs($this->superadmin)
        ->post("/admin/contingencia/{$this->empresa->id}/desactivar", [
            'resolucion' => 'Labores concluidas por administración central.',
        ]);

    $desactivaResponse->assertRedirect();
    expect($this->empresa->fresh()->estaEnContingencia())->toBeFalse();
});

test('procesar baja formal de cliente revoca accesos de usuarios y programa retención legal a 24 meses', function () {
    // Verificar que el usuario administrador está inicialmente activo
    $usuarioVinculo = DB::table('empresa_usuario')
        ->where('empresa_id', $this->empresa->id)
        ->where('user_id', $this->adminUsuario->id)
        ->first();
    expect((bool) $usuarioVinculo->activo)->toBeTrue();

    $response = $this->actingAs($this->superadmin)
        ->post("/admin/empresas/{$this->empresa->id}/baja", [
            'motivo' => 'Finalización voluntaria de contrato y solicitud de entrega de datos.',
            'meses_retencion' => 24,
        ]);

    $response->assertRedirect();
    $this->empresa->refresh();

    expect($this->empresa->estado)->toBe(EstadoEmpresa::BajaProgramada)
        ->and($this->empresa->activo)->toBeFalse()
        ->and($this->empresa->fecha_baja)->not->toBeNull()
        ->and($this->empresa->retencion_hasta->toDateString())->toBe('2028-11-09') // 24 meses desde 2026-11-09
        ->and($this->empresa->salida_autorizada_por_id)->toBe($this->superadmin->id);

    // Criterio de aceptación: revocación inmediata de accesos del cliente
    $usuarioVinculoActualizado = DB::table('empresa_usuario')
        ->where('empresa_id', $this->empresa->id)
        ->where('user_id', $this->adminUsuario->id)
        ->first();
    expect((bool) $usuarioVinculoActualizado->activo)->toBeFalse();

    // Se registró en auditoría
    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'baja_empresa',
    ]);
});

test('paquete de custodia legal exporta un ZIP íntegro con expedientes, auditoría, acta legal y hash SHA-256', function () {
    $salidaService = app(SalidaClienteService::class);
    $paquete = $salidaService->generarPaqueteCustodia($this->empresa, $this->superadmin);

    expect($paquete)->toHaveKeys(['path', 'filename', 'hash', 'size'])
        ->and(file_exists($paquete['path']))->toBeTrue()
        ->and(strlen($paquete['hash']))->toBe(64); // SHA-256

    // Verificar contenido del ZIP
    $zip = new ZipArchive;
    expect($zip->open($paquete['path']))->toBeTrue();

    // Contiene los archivos obligatorios de custodia
    expect($zip->locateName('expedientes_reclamaciones.csv'))->not->toBeFalse()
        ->and($zip->locateName('bitacora_auditoria.csv'))->not->toBeFalse()
        ->and($zip->locateName('ACTA_CUSTODIA_LEGAL.txt'))->not->toBeFalse();

    // Verificar que el acta legal contiene los datos exigidos
    $actaContenido = $zip->getFromName('ACTA_CUSTODIA_LEGAL.txt');
    expect($actaContenido)->toContain('COMERCIAL ANDINA S.A.C.')
        ->and($actaContenido)->toContain('20123456789')
        ->and($actaContenido)->toContain('Ley N° 29571')
        ->and($actaContenido)->toContain('24 meses');

    $zip->close();

    // Limpieza de archivo de prueba
    if (file_exists($paquete['path'])) {
        unlink($paquete['path']);
    }
});

test('portal público bloquea el acceso si la empresa se encuentra en baja programada bajo custodia legal', function () {
    $this->empresa->update([
        'estado' => EstadoEmpresa::BajaProgramada,
        'activo' => false,
        'fecha_baja' => now(),
        'retencion_hasta' => now()->addMonths(24)->toDateString(),
    ]);

    $response = $this->get('/l/comercial-andina');
    $response->assertStatus(403);
});

test('superadministrador puede descargar el paquete de custodia probatoria vía endpoint seguro', function () {
    // Procesar baja primero
    app(SalidaClienteService::class)->procesarBaja($this->empresa, 'Cierre de operaciones', $this->superadmin, 24);

    $response = $this->actingAs($this->superadmin)
        ->get("/admin/empresas/{$this->empresa->id}/custodia/descargar");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');
});
