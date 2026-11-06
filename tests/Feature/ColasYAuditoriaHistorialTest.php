<?php

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Jobs\EnviarConstanciaReclamacionJob;
use App\Mail\RespuestaReclamacionMailable;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
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