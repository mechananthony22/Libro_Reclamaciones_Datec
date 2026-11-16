<?php

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionEvento;
use App\Models\ReclamacionRespuesta;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->empresa = Empresa::create([
        'name' => 'Empresa Inmutable SAC',
        'razon_social' => 'EMPRESA INMUTABLE S.A.C.',
        'ruc' => '20998877665',
        'direccion' => 'Av. Inmutable 123',
        'slug' => 'empresa-inmutable',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Pro,
    ]);

    $this->reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresa->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now()->subDay(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Televisor Smart 55',
        'monto_reclamado' => 1999.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'Pantalla rota al entregar.',
        'pedido_consumidor' => 'Cambio de equipo.',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan.perez@test.com',
            'telefono' => '987654321',
            'direccion' => 'Jr. Lima 123',
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA INMUTABLE S.A.C.',
            'ruc' => '20998877665',
        ],
        'estado' => EstadoReclamacion::Pendiente,
        'token_consulta' => Str::random(40),
    ]);
});

test('reclamacion bloquea cualquier intento de modificar campos inmutables de origen', function () {
    // Intentar modificar número de hoja
    expect(fn () => $this->reclamacion->update(['numero_hoja' => '00000999-2026']))
        ->toThrow(DomainException::class);

    // Intentar modificar datos del consumidor
    expect(fn () => $this->reclamacion->update(['datos_consumidor' => ['nombres' => 'Hacker']]))
        ->toThrow(DomainException::class);

    // Intentar modificar detalle del problema
    expect(fn () => $this->reclamacion->update(['detalle_problema' => 'Texto alterado']))
        ->toThrow(DomainException::class);

    // Intentar modificar pedido del consumidor
    expect(fn () => $this->reclamacion->update(['pedido_consumidor' => 'Pedido alterado']))
        ->toThrow(DomainException::class);

    // Intentar modificar token de consulta
    expect(fn () => $this->reclamacion->update(['token_consulta' => 'token_hackeado']))
        ->toThrow(DomainException::class);

    // Intentar eliminar la reclamación
    expect(fn () => $this->reclamacion->delete())
        ->toThrow(DomainException::class);
});

test('reclamacion permite actualizar unicamente campos del flujo operativo como estado y responsable', function () {
    $usuario = User::create([
        'name' => 'Responsable Asignado',
        'email' => 'responsable@test.com',
        'password' => Hash::make('secret123'),
    ]);

    // Modificar estado es permitido
    $this->reclamacion->update([
        'estado' => EstadoReclamacion::EnTramite,
        'asignado_a' => $usuario->id,
        'asignado_en' => now(),
    ]);

    $this->reclamacion->refresh();
    expect($this->reclamacion->estado)->toBe(EstadoReclamacion::EnTramite)
        ->and($this->reclamacion->asignado_a)->toBe($usuario->id);
});

test('reclamacion_eventos es inmutable y bloquea actualizacion y eliminacion', function () {
    $evento = ReclamacionEvento::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'actor_tipo' => 'sistema',
        'actor_nombre' => 'Sistema',
        'accion' => 'reclamacion_registrada',
        'descripcion' => 'Reclamación registrada formalmente.',
        'created_at' => now(),
    ]);

    expect(fn () => $evento->update(['descripcion' => 'Evento alterado']))
        ->toThrow(DomainException::class);

    expect(fn () => $evento->delete())
        ->toThrow(DomainException::class);
});

test('auditoria_logs es inmutable y bloquea actualizacion y eliminacion individual', function () {
    $log = AuditoriaLog::create([
        'empresa_id' => $this->empresa->id,
        'actor_tipo' => 'sistema',
        'actor_nombre' => 'Sistema',
        'modulo' => 'expedientes',
        'accion' => 'expediente_consultado',
        'descripcion' => 'Consulta de expediente.',
        'created_at' => now(),
    ]);

    expect(fn () => $log->update(['descripcion' => 'Log alterado']))
        ->toThrow(DomainException::class);

    expect(fn () => $log->delete())
        ->toThrow(DomainException::class);
});

test('reclamacion_respuestas bloquea eliminacion para salvaguardar evidencia legal', function () {
    $respuesta = ReclamacionRespuesta::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $this->reclamacion->id,
        'autor_nombre' => 'Abogado Defensor',
        'decision' => DecisionRespuesta::Procedente,
        'propuesta_solucion' => 'Se acepta la devolución del monto.',
        'version' => 1,
        'fecha_respuesta' => now(),
    ]);

    expect(fn () => $respuesta->delete())
        ->toThrow(DomainException::class);
});
