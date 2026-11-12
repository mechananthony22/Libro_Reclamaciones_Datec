<?php

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Jobs\EnviarConstanciaReclamacionJob;
use App\Jobs\NotificarNuevaReclamacionEmpresaJob;
use App\Mail\NuevaReclamacionEmpresaMailable;
use App\Mail\RespuestaReclamacionMailable;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionEnvio;
use App\Models\ReclamacionRespuesta;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Tenant;

beforeEach(function () {
    Storage::fake('local');

    $this->empresaA = Empresa::create([
        'name' => 'Corporacion Lima SAC',
        'razon_social' => 'CORPORACION LIMA S.A.C.',
        'ruc' => '20101010101',
        'direccion' => 'Av. Arequipa 100, Lima',
        'email' => 'contacto@lima.pe',
        'slug' => 'corporacion-lima',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Industrias Cusco SAC',
        'razon_social' => 'INDUSTRIAS CUSCO S.A.C.',
        'ruc' => '20202020202',
        'direccion' => 'Av. El Sol 200, Cusco',
        'email' => 'contacto@cusco.pe',
        'slug' => 'industrias-cusco',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Global',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => true,
    ]);

    $this->adminA = User::create([
        'name' => 'Administrador Lima',
        'email' => 'admin@lima.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => false,
    ]);

    $this->adminA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->adminB = User::create([
        'name' => 'Administrador Cusco',
        'email' => 'admin@cusco.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => false,
    ]);

    $this->adminB->empresas()->attach($this->empresaB->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);
});

test('los trabajos de notificaciones y constancias se procesan mediante colas preservando el contexto tenant', function () {
    Mail::fake();

    $reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Televisor',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => [
            'nombres' => 'Alberto',
            'apellidos' => 'Flores',
            'email' => 'alberto@correo.pe',
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'CORPORACION LIMA S.A.C.',
            'ruc' => '20101010101',
            'direccion' => 'Av. Arequipa 100, Lima',
        ],
    ]);

    // Simular que el worker arranca sin tenant activo o con otro contexto
    Tenant::forgetCurrent();

    $job = new EnviarConstanciaReclamacionJob($reclamacion);
    $job->handle();

    // El job debe haber restaurado el contexto tenant correcto
    expect(Empresa::current()?->id)->toBe($this->empresaA->id);
});

test('el correo de respuesta formal descarta adjuntos que pertenezcan a otros tenants garantizando aislamiento', function () {
    // Crear archivo legítimo de la empresa A
    $rutaLegitima = "empresas/{$this->empresaA->id}/respuestas/1/informe.pdf";
    Storage::disk('local')->put($rutaLegitima, 'Contenido informe A');

    // Crear archivo ajeno perteneciente a la empresa B
    $rutaAjena = "empresas/{$this->empresaB->id}/respuestas/99/secreto.pdf";
    Storage::disk('local')->put($rutaAjena, 'Contenido confidencial B');

    $reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '00000005-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Laptop',
        'estado' => EstadoReclamacion::Atendida,
        'datos_consumidor' => [
            'nombres' => 'Elena',
            'apellidos' => 'Rios',
            'email' => 'elena@correo.pe',
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'CORPORACION LIMA S.A.C.',
            'ruc' => '20101010101',
            'direccion' => 'Av. Arequipa 100, Lima',
        ],
    ]);

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresaA->id,
        'reclamacion_id' => $reclamacion->id,
        'user_id' => $this->adminA->id,
        'version' => 1,
        'fecha_respuesta' => now(),
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Reemplazo total',
        'autor_nombre' => 'Admin Lima',
        'adjuntos_paths' => [
            ['path' => $rutaLegitima, 'nombre' => 'informe.pdf'],
            ['path' => $rutaAjena, 'nombre' => 'secreto_ajeno.pdf'], // Intento de adjunto ajeno
        ],
    ]);

    $mailable = new RespuestaReclamacionMailable($reclamacion, $respuesta);
    $adjuntos = $mailable->attachments();

    // Debe contener el archivo propio de empresa A pero NUNCA el de empresa B
    expect(count($adjuntos))->toBe(1);
});

test('registra eventos inmutables de auditoria identificando actor tenant accion y fecha', function () {
    $service = app(AuditoriaService::class);

    $log = $service->registrar(
        accion: 'prueba_auditoria',
        modulo: 'seguridad',
        descripcion: 'Evento de prueba para validación de bitácora',
        opciones: [
            'empresa_id' => $this->empresaA->id,
            'user' => $this->adminA,
            'datos_despues' => ['parametro' => 'valor'],
        ]
    );

    expect($log)->toBeInstanceOf(AuditoriaLog::class);
    expect($log->empresa_id)->toBe($this->empresaA->id);
    expect($log->user_id)->toBe($this->adminA->id);
    expect($log->actor_nombre)->toBe('Administrador Lima');
    expect($log->actor_email)->toBe('admin@lima.pe');
    expect($log->accion)->toBe('prueba_auditoria');
    expect($log->modulo)->toBe('seguridad');
    expect($log->created_at)->not->toBeNull();

    $this->assertDatabaseHas('auditoria_logs', [
        'id' => $log->id,
        'empresa_id' => $this->empresaA->id,
        'accion' => 'prueba_auditoria',
    ]);
});

test('solo el superadministrador puede acceder a la bitacora global de auditoria', function () {
    // Usuario no autenticado es redirigido a login
    $responseGuest = $this->get(route('admin.auditoria.index'));
    $responseGuest->assertRedirect(route('login'));

    // Administrador de empresa no tiene acceso al panel de auditoría global
    $this->empresaA->makeCurrent();
    $responseAdminEmpresa = $this->actingAs($this->adminA)
        ->get(route('admin.auditoria.index'));
    $responseAdminEmpresa->assertForbidden();

    // Superadministrador Datec tiene acceso autorizado
    $responseSuperadmin = $this->actingAs($this->superadmin)
        ->get(route('admin.auditoria.index'));
    $responseSuperadmin->assertOk();
    $responseSuperadmin->assertInertia(fn ($page) => $page->component('Admin/Auditoria/Index'));
});

test('el historial de auditoria de la empresa respeta estrictamente el aislamiento multitenant', function () {
    $service = app(AuditoriaService::class);

    // Evento de empresa A
    $service->registrar(
        accion: 'configuracion_actualizada',
        modulo: 'configuracion',
        descripcion: 'Cambio en empresa Lima',
        opciones: ['empresa_id' => $this->empresaA->id, 'user' => $this->adminA]
    );

    // Evento de empresa B
    $service->registrar(
        accion: 'configuracion_actualizada',
        modulo: 'configuracion',
        descripcion: 'Cambio confidencial en empresa Cusco',
        opciones: ['empresa_id' => $this->empresaB->id, 'user' => $this->adminB]
    );

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.auditoria.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Auditoria/Index')
        ->has('logs.data', 1)
        ->where('logs.data.0.descripcion', 'Cambio en empresa Lima')
    );
});

test('el endpoint alias /empresa/auditoria responde correctamente con el historial del tenant', function () {
    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get('/empresa/auditoria');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Empresa/Auditoria/Index'));
});

test('el administrador de la empresa puede exportar su bitacora de auditoria a CSV con aislamiento estricto', function () {
    $service = app(AuditoriaService::class);

    $service->registrar(
        accion: 'login_exitoso',
        modulo: 'seguridad',
        descripcion: 'Inicio de sesion Lima',
        opciones: ['empresa_id' => $this->empresaA->id, 'user' => $this->adminA]
    );

    $service->registrar(
        accion: 'login_exitoso',
        modulo: 'seguridad',
        descripcion: 'Inicio de sesion confidencial Cusco',
        opciones: ['empresa_id' => $this->empresaB->id, 'user' => $this->adminB]
    );

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.auditoria.exportar'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    // Capturar contenido streamed
    ob_start();
    $response->sendContent();
    $csvOutput = ob_get_clean();

    expect($csvOutput)->toContain('Inicio de sesion Lima')
        ->and($csvOutput)->not->toContain('Inicio de sesion confidencial Cusco');

    // Verificar que la acción de exportación se auditó
    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresaA->id,
        'accion' => 'auditoria_exportada',
    ]);
});

test('comando de purga de auditoria bloquea ejecucion si se solicitan menos de 24 meses sin flag force', function () {
    $this->artisan('auditoria:purgar', ['--meses' => 12])
        ->expectsOutputToContain('La normativa peruana (Ley N° 29571 / D.S. N° 011-2011-PCM) exige conservar las reclamaciones y su trazabilidad durante al menos 24 meses.')
        ->assertFailed();
});

test('comando de purga de auditoria elimina logs que superan la antiguedad legal respetando logs recientes', function () {
    // 1. Crear un log antiguo de hace 26 meses (debe ser purgado)
    $logAntiguo = AuditoriaLog::create([
        'empresa_id' => $this->empresaA->id,
        'actor_tipo' => 'sistema',
        'actor_nombre' => 'Sistema',
        'modulo' => 'seguridad',
        'accion' => 'evento_antiguo',
        'descripcion' => 'Evento ocurrido hace 26 meses',
        'created_at' => now()->subMonths(26),
    ]);

    // 2. Crear un log reciente de hace 2 meses (debe conservarse)
    $logReciente = AuditoriaLog::create([
        'empresa_id' => $this->empresaA->id,
        'actor_tipo' => 'sistema',
        'actor_nombre' => 'Sistema',
        'modulo' => 'seguridad',
        'accion' => 'evento_reciente',
        'descripcion' => 'Evento ocurrido hace 2 meses',
        'created_at' => now()->subMonths(2),
    ]);

    // 3. Simulación dry-run no debe borrar nada
    $this->artisan('auditoria:purgar', ['--meses' => 24, '--dry-run' => true])
        ->expectsOutputToContain('[MODO SIMULACIÓN - DRY RUN]')
        ->assertSuccessful();

    expect(AuditoriaLog::find($logAntiguo->id))->not->toBeNull();
    expect(AuditoriaLog::find($logReciente->id))->not->toBeNull();

    // 4. Ejecución real con plazo legal de 24 meses
    $this->artisan('auditoria:purgar', ['--meses' => 24])
        ->expectsOutputToContain('Purga completada exitosamente')
        ->assertSuccessful();

    // El log antiguo debe haber sido eliminado
    expect(AuditoriaLog::find($logAntiguo->id))->toBeNull();
    // El log reciente debe permanecer intacto
    expect(AuditoriaLog::find($logReciente->id))->not->toBeNull();
});

test('notificar nueva reclamacion a la empresa envia correos respetando columnas reales de envio y copia oculta', function () {
    Mail::fake();

    $this->empresaA->update([
        'email' => 'principal@empresa-a.pe',
        'representante_email' => 'representante@empresa-a.pe',
        'emails_notificaciones' => ['operaciones@empresa-a.pe'],
        'notificar_nuevo_reclamo' => true,
    ]);

    $reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '000055-2026',
        'tipo' => TipoReclamacion::Reclamo,
        'estado' => EstadoReclamacion::Pendiente,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Servidor Blade',
        'detalle_problema' => 'Falla de fuente redundantemente.',
        'pedido_consumidor' => 'Reemplazo de pieza.',
        'aviso_privacidad_leido' => true,
        'aviso_privacidad_version' => '1.0',
        'aviso_privacidad_aceptado_en' => now(),
        'datos_consumidor' => ['nombres' => 'Cliente', 'apellidos' => 'Empresarial', 'email' => 'cliente@test.com'],
        'datos_legales_snapshot' => ['razon_social' => 'CORPORACION LIMA S.A.C.', 'ruc' => '20101010101'],
        'fecha_hora' => now(),
    ]);

    $job = new NotificarNuevaReclamacionEmpresaJob($reclamacion);
    $job->handle();

    // 1. Debe haber enviado el correo
    Mail::assertSent(NuevaReclamacionEmpresaMailable::class, function ($mail) {
        return $mail->hasTo('operaciones@empresa-a.pe')
            || $mail->hasTo('principal@empresa-a.pe');
    });

    // 2. Debe haber registrado los envíos en reclamacion_envios con las columnas reales
    $envios = ReclamacionEnvio::where('reclamacion_id', $reclamacion->id)->get();
    expect($envios)->toHaveCount(3);

    foreach ($envios as $envio) {
        expect($envio->tipo)->toBe('alerta_nueva_empresa')
            ->and($envio->canal)->toBe('email')
            ->and($envio->estado)->toBe('enviado')
            ->and($envio->enviado_en)->not->toBeNull()
            ->and($envio->intentos)->toBe(1);
    }

    // 3. Debe haberse registrado el evento en la auditoría del expediente
    $this->assertDatabaseHas('reclamacion_eventos', [
        'empresa_id' => $this->empresaA->id,
        'reclamacion_id' => $reclamacion->id,
        'accion' => 'notificacion_empresa_enviada',
    ]);
});

test('notificar nueva reclamacion a la empresa respeta cuando notificar_nuevo_reclamo esta desactivado', function () {
    Mail::fake();

    $this->empresaA->update([
        'notificar_nuevo_reclamo' => false,
    ]);

    $reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '000056-2026',
        'tipo' => TipoReclamacion::Reclamo,
        'estado' => EstadoReclamacion::Pendiente,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Internet dedicado',
        'detalle_problema' => 'Corte de servicio',
        'pedido_consumidor' => 'Restablecimiento',
        'aviso_privacidad_leido' => true,
        'aviso_privacidad_version' => '1.0',
        'aviso_privacidad_aceptado_en' => now(),
        'datos_consumidor' => ['nombres' => 'Cliente', 'apellidos' => 'Silencioso'],
        'datos_legales_snapshot' => ['razon_social' => 'CORPORACION LIMA S.A.C.', 'ruc' => '20101010101'],
        'fecha_hora' => now(),
    ]);

    $job = new NotificarNuevaReclamacionEmpresaJob($reclamacion);
    $job->handle();

    // No debe enviar ningún correo
    Mail::assertNothingSent();

    // No debe crear registros de envío
    expect(ReclamacionEnvio::where('reclamacion_id', $reclamacion->id)->count())->toBe(0);
});
