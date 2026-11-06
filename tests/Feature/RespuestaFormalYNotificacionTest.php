<?php

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Jobs\EnviarRespuestaReclamacionJob;
use App\Mail\RespuestaReclamacionMailable;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionEnvio;
use App\Models\ReclamacionEvento;
use App\Models\ReclamacionRespuesta;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');

    $this->empresa = Empresa::create([
        'name' => 'Empresa Retail SAC',
        'razon_social' => 'EMPRESA RETAIL S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Javier Prado 100, Lima',
        'slug' => 'empresa-retail',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->usuario = User::create([
        'name' => 'Especialista Legal',
        'email' => 'legal@retail.pe',
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
        'fecha_hora' => now()->subDays(3),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Laptop Gamer 16GB',
        'monto_reclamado' => 3500.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'El teclado dejó de responder tras actualización.',
        'pedido_consumidor' => 'Reparación por garantía o cambio de teclado.',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '45678912',
            'nombres' => 'Mario',
            'apellidos' => 'Vargas',
            'email' => 'mario.vargas@ejemplo.com',
            'telefono' => '987123456',
            'direccion' => 'Av. Los Álamos 450',
            'es_menor_edad' => false,
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA RETAIL S.A.C.',
            'ruc' => '20123456789',
            'direccion' => 'Av. Javier Prado 100, Lima',
        ],
        'estado' => EstadoReclamacion::EnTramite,
        'vence_en' => now()->addWeekdays(12)->toDateString(),
        'token_consulta' => Str::random(40),
    ]);
});

test('un usuario autorizado puede registrar una respuesta formal escrita con decision fundamentada', function () {
    Queue::fake();

    $archivoPrueba = UploadedFile::fake()->create('informe_tecnico.pdf', 300, 'application/pdf');

    $response = $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id])
        ->post(route('empresa.expedientes.respuestas.store', ['id' => $this->reclamacion->id]), [
            'decision' => DecisionRespuesta::Procedente->value,
            'propuesta_solucion' => 'Se procederá con el cambio total del teclado en el servicio técnico oficial.',
            'fundamento_legal' => 'Conforme a la garantía legal del artículo 20 de la Ley N° 29571.',
            'autor_cargo' => 'Jefe de Garantías y Postventa',
            'notificar_inmediatamente' => true,
            'adjuntos' => [$archivoPrueba],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Comprobar persistencia de la respuesta formal
    $respuesta = ReclamacionRespuesta::where('reclamacion_id', $this->reclamacion->id)->first();
    expect($respuesta)->not->toBeNull()
        ->and($respuesta->decision)->toBe(DecisionRespuesta::Procedente)
        ->and($respuesta->autor_nombre)->toBe('Especialista Legal')
        ->and($respuesta->autor_cargo)->toBe('Jefe de Garantías y Postventa')
        ->and($respuesta->version)->toBe(1)
        ->and(count($respuesta->adjuntos_paths))->toBe(1);

    // Comprobar actualización automática de estado a Atendida
    $this->reclamacion->refresh();
    expect($this->reclamacion->estado)->toBe(EstadoReclamacion::Atendida);

    // Comprobar que se encoló el trabajo de notificación
    Queue::assertPushed(EnviarRespuestaReclamacionJob::class);

    // Comprobar evento de auditoría
    $evento = ReclamacionEvento::where('reclamacion_id', $this->reclamacion->id)
        ->where('accion', 'respuesta_formal_registrada')
        ->first();

    expect($evento)->not->toBeNull()
        ->and($evento->descripcion)->toContain('Procedente (Aceptado)')
        ->and($evento->actor_id)->toBe($this->usuario->id);
});

test('el trabajo de envio notifica por correo al consumidor y registra la evidencia de entrega', function () {
    Mail::fake();

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Especialista Legal',
        'autor_cargo' => 'Jefe de Garantías',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solución integral del reclamo.',
        'version' => 1,
        'fecha_respuesta' => now(),
        'notificado' => false,
    ]);

    $job = new EnviarRespuestaReclamacionJob($this->reclamacion, $respuesta);
    $job->handle();

    // Comprobar que se envió el Mailable a la dirección de correo registrada
    Mail::assertSent(RespuestaReclamacionMailable::class, function ($mail) {
        return $mail->hasTo('mario.vargas@ejemplo.com')
            && str_contains($mail->envelope()->subject, 'Respuesta Formal');
    });

    // Comprobar registro de auditoría de envío
    $envio = ReclamacionEnvio::where('reclamacion_id', $this->reclamacion->id)
        ->where('tipo', 'respuesta_formal')
        ->first();

    expect($envio)->not->toBeNull()
        ->and($envio->estado)->toBe('enviado')
        ->and($envio->destinatario)->toBe('mario.vargas@ejemplo.com');

    $respuesta->refresh();
    expect($respuesta->notificado)->toBeTrue()
        ->and($respuesta->notificado_en)->not->toBeNull();
});

test('se puede reintentar manualmente la notificacion de la respuesta', function () {
    Queue::fake();

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Especialista Legal',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solución acordada.',
        'version' => 1,
        'fecha_respuesta' => now(),
        'notificado' => false,
    ]);

    $response = $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id])
        ->post(route('empresa.expedientes.respuestas.reenviar', [
            'id' => $this->reclamacion->id,
            'respuestaId' => $respuesta->id,
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Queue::assertPushed(EnviarRespuestaReclamacionJob::class);
});

test('registra evidencia y alerta cuando el consumidor no tiene correo valido', function () {
    $this->reclamacion->update([
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '45678912',
            'nombres' => 'Mario',
            'apellidos' => 'Vargas',
        ],
    ]);

    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Especialista Legal',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solucion integral del reclamo.',
        'version' => 1,
        'fecha_respuesta' => now(),
        'notificado' => false,
    ]);

    (new EnviarRespuestaReclamacionJob($this->reclamacion, $respuesta))->handle();

    $envio = ReclamacionEnvio::where('reclamacion_id', $this->reclamacion->id)
        ->where('tipo', 'respuesta_formal')
        ->first();

    expect($envio)->not->toBeNull()
        ->and($envio->estado)->toBe('fallido')
        ->and($envio->error_mensaje)->toContain('para recibir la respuesta formal');

    $this->assertDatabaseHas('reclamacion_eventos', [
        'reclamacion_id' => $this->reclamacion->id,
        'accion' => 'notificacion_respuesta_sin_destinatario',
    ]);
});

test('al agotar los reintentos deja el envio fallido y registra alerta definitiva', function () {
    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'user_id' => $this->usuario->id,
        'autor_nombre' => 'Especialista Legal',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Solucion integral del reclamo.',
        'version' => 1,
        'fecha_respuesta' => now(),
        'notificado' => false,
    ]);

    ReclamacionEnvio::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'tipo' => 'respuesta_formal',
        'destinatario' => 'mario.vargas@ejemplo.com',
        'canal' => 'email',
        'estado' => 'pendiente',
        'intentos' => 3,
    ]);

    (new EnviarRespuestaReclamacionJob($this->reclamacion, $respuesta))
        ->failed(new RuntimeException('Servidor SMTP no disponible'));

    $envio = ReclamacionEnvio::where('reclamacion_id', $this->reclamacion->id)
        ->where('tipo', 'respuesta_formal')
        ->first();

    expect($envio->estado)->toBe('fallido')
        ->and($envio->error_mensaje)->toBe('Servidor SMTP no disponible');

    $this->assertDatabaseHas('reclamacion_eventos', [
        'reclamacion_id' => $this->reclamacion->id,
        'accion' => 'notificacion_respuesta_fallida_definitiva',
    ]);
});
