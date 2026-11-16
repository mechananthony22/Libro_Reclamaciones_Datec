<?php

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionRespuesta;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');

    $this->empresa = Empresa::create([
        'name' => 'Empresa Test SAC',
        'razon_social' => 'EMPRESA TEST S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Test 123',
        'slug' => 'empresa-test',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->usuario = User::create([
        'name' => 'Admin Empresa',
        'email' => 'admin@test.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $this->usuario->empresas()->attach($this->empresa->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresa->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Teclado Mecánico',
        'monto_reclamado' => 250.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'Falla en teclas de función.',
        'pedido_consumidor' => 'Cambio de producto o reembolso.',
        'estado' => EstadoReclamacion::Pendiente,
        'token_consulta' => Str::random(40),
        'datos_consumidor' => [
            'nombre' => 'Carlos Cliente',
            'email' => 'carlos@cliente.pe',
            'telefono' => '999888777',
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA TEST S.A.C.',
            'ruc' => '20123456789',
            'direccion' => 'Av. Test 123',
        ],
    ]);
});

test('P2-05: no permite cambiar estado a Atendida o Cerrada si no existe respuesta formal previa', function () {
    $this->empresa->makeCurrent();

    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    // Intentar cambiar a atendida sin respuesta previa debe fallar
    $response = $this->patch(route('empresa.expedientes.estado', $this->reclamacion->id), [
        'estado' => 'atendida',
        'motivo' => 'Intento de cierre directo sin respuesta legal',
    ]);

    $response->assertSessionHasErrors('estado');
    expect($this->reclamacion->fresh()->estado)->toBe(EstadoReclamacion::Pendiente);

    // Cambiar a en_tramite sin respuesta sí debe permitirse
    $responseTramite = $this->patch(route('empresa.expedientes.estado', $this->reclamacion->id), [
        'estado' => 'en_tramite',
        'motivo' => 'Expediente derivado a soporte técnico',
    ]);

    $responseTramite->assertSessionHasNoErrors();
    expect($this->reclamacion->fresh()->estado)->toBe(EstadoReclamacion::EnTramite);
});

test('P2-05: permite cambiar estado a Atendida cuando ya existe una respuesta formal registrada', function () {
    $this->empresa->makeCurrent();

    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    // Creamos la respuesta formal
    ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Admin Test',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Se procederá al cambio inmediato del equipo.',
        'fundamento_legal' => 'Art. 97 del Código de Protección y Defensa del Consumidor.',
        'fecha_respuesta' => now(),
        'notificado_por_email' => false,
    ]);

    // Ahora el cambio a atendida debe ser exitoso
    $response = $this->patch(route('empresa.expedientes.estado', $this->reclamacion->id), [
        'estado' => 'atendida',
        'motivo' => 'Respuesta formal emitida y conforme',
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->reclamacion->fresh()->estado)->toBe(EstadoReclamacion::Atendida);
});

test('P2-06: ExpedienteController::show sanitiza adjuntos y no expone rutas del servidor', function () {
    $this->empresa->makeCurrent();

    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $fakePath = "empresas/{$this->empresa->id}/respuestas/1/informe_tecnico.pdf";
    Storage::disk('local')->put($fakePath, 'Contenido PDF de prueba');

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Admin Test',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solución aceptada con adjuntos.',
        'fecha_respuesta' => now(),
        'adjuntos_paths' => [
            [
                'nombre' => 'informe_tecnico.pdf',
                'path' => $fakePath,
                'tamano_bytes' => 1024,
                'mime_type' => 'application/pdf',
            ],
        ],
        'notificado_por_email' => false,
    ]);

    $response = $this->get(route('empresa.expedientes.show', $this->reclamacion->id));
    $response->assertOk();

    // Verificamos que Inertia reciba los datos sanitizados
    $response->assertInertia(function ($page) {
        $page->component('Empresa/Expedientes/Show')
            ->has('expediente.respuestas.0.adjuntos.0', function ($adjunto) {
                $adjunto->where('nombre', 'informe_tecnico.pdf')
                    ->where('indice', 0)
                    ->has('url_descarga')
                    ->missing('path')
                    ->etc(); // ¡NO debe exponer el path interno!
            });
    });
});

test('P2-06: descarga de adjuntos protegida para usuarios de la empresa y bloqueada para otras empresas', function () {
    $fakePath = "empresas/{$this->empresa->id}/respuestas/1/documento.pdf";
    Storage::disk('local')->put($fakePath, 'Contenido de documento privado');

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Admin Test',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solución',
        'fecha_respuesta' => now(),
        'adjuntos_paths' => [
            [
                'nombre' => 'documento.pdf',
                'path' => $fakePath,
                'tamano_bytes' => 100,
                'mime_type' => 'application/pdf',
            ],
        ],
        'notificado_por_email' => false,
    ]);

    // Usuario autenticado de la misma empresa puede descargar
    $this->empresa->makeCurrent();
    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $response = $this->get(route('empresa.expedientes.respuestas.adjunto.descargar', [
        'id' => $this->reclamacion->id,
        'respuestaId' => $respuesta->id,
        'indice' => 0,
    ]));

    $response->assertOk();
    $response->assertDownload('documento.pdf');

    // Usuario de OTRA empresa recibe 404
    $otraEmpresa = Empresa::create([
        'name' => 'Otra Empresa SAC',
        'razon_social' => 'OTRA S.A.C.',
        'ruc' => '20999999999',
        'direccion' => 'Av. Otra 456',
        'slug' => 'otra-empresa',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $otroUsuario = User::create([
        'name' => 'Otro Admin',
        'email' => 'otro@empresa.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);
    $otroUsuario->empresas()->attach($otraEmpresa->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $otraEmpresa->makeCurrent();
    $this->actingAs($otroUsuario)
        ->withSession(['current_tenant_id' => $otraEmpresa->id]);

    $responseOtra = $this->get(route('empresa.expedientes.respuestas.adjunto.descargar', [
        'id' => $this->reclamacion->id,
        'respuestaId' => $respuesta->id,
        'indice' => 0,
    ]));

    $responseOtra->assertNotFound();
});

test('P2-06: consulta pública sanitiza adjuntos y permite descarga segura mediante token', function () {
    $fakePath = "empresas/{$this->empresa->id}/respuestas/1/carta_respuesta.pdf";
    Storage::disk('local')->put($fakePath, 'PDF de respuesta oficial para consumidor');

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Admin Test',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Respuesta formal emitida',
        'fecha_respuesta' => now(),
        'adjuntos_paths' => [
            [
                'nombre' => 'carta_respuesta.pdf',
                'path' => $fakePath,
                'tamano_bytes' => 500,
                'mime_type' => 'application/pdf',
            ],
        ],
        'notificado_por_email' => false,
    ]);

    // Visita a consulta segura con enlace temporal firmado
    $urlFirmada = URL::temporarySignedRoute(
        'libro.consulta.firmada',
        now()->addMinutes(30),
        [
            'slug' => $this->empresa->slug,
            'token' => $this->reclamacion->token_consulta,
        ]
    );

    $responsePublico = $this->get($urlFirmada);

    $responsePublico->assertOk();
    $responsePublico->assertInertia(function ($page) {
        $page->component('Publico/ConsultaEstado')
            ->has('expediente.respuesta.adjuntos.0', function ($adjunto) {
                $adjunto->where('nombre', 'carta_respuesta.pdf')
                    ->where('indice', 0)
                    ->has('url_descarga')
                    ->missing('path')
                    ->etc(); // ¡No expone rutas privadas!
            });
    });

    // Descarga pública con token válido
    $responseDescarga = $this->get(route('libro.consulta.respuesta.adjunto', [
        'slug' => $this->empresa->slug,
        'token' => $this->reclamacion->token_consulta,
        'respuestaId' => $respuesta->id,
        'indice' => 0,
    ]));

    $responseDescarga->assertOk();
    $responseDescarga->assertDownload('carta_respuesta.pdf');

    // Descarga pública con token INVÁLIDO debe ser 404
    $responseInvalido = $this->get(route('libro.consulta.respuesta.adjunto', [
        'slug' => $this->empresa->slug,
        'token' => 'token-invalido-o-falso',
        'respuestaId' => $respuesta->id,
        'indice' => 0,
    ]));

    $responseInvalido->assertNotFound();
});

test('P2-07: no duplica registros de auditoria al cambiar estado o registrar respuesta', function () {
    $this->empresa->makeCurrent();
    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    // 1. Cambiar estado a en_tramite
    $this->patch(route('empresa.expedientes.estado', $this->reclamacion->id), [
        'estado' => 'en_tramite',
        'motivo' => 'Derivando a área legal para evaluación',
    ])->assertSessionHasNoErrors();

    // Debe existir exactamente 1 registro de auditoría, NO 2
    $logsEstado = AuditoriaLog::where('accion', 'expediente_estado_cambiado')->count();
    expect($logsEstado)->toBe(1);

    // 2. Registrar respuesta formal
    $this->post(route('empresa.expedientes.respuestas.store', $this->reclamacion->id), [
        'decision' => DecisionRespuesta::Procedente->value,
        'propuesta_solucion' => 'Se procederá al cambio del producto en un plazo de 48 horas.',
        'fundamento_legal' => 'Art. 97 del Código de Protección y Defensa del Consumidor.',
        'autor_cargo' => 'Jefe de Garantías',
        'notificar_inmediatamente' => false,
    ])->assertSessionHasNoErrors();

    // Debe existir exactamente 1 registro de respuesta_formal_registrada, NO 2
    $logsRespuesta = AuditoriaLog::where('accion', 'respuesta_formal_registrada')->count();
    expect($logsRespuesta)->toBe(1);
});
