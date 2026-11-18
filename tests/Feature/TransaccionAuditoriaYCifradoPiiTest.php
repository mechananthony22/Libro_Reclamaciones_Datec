<?php

use App\Actions\Reclamaciones\ConsultarEstadoReclamacionAction;
use App\Actions\Reclamaciones\RegistrarReclamacionAction;
use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionEvento;
use Illuminate\Support\Facades\DB;

test('P2-15: aceptar invitacion se ejecuta en transaccion y genera log de AuditoriaService', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Transaccional',
        'slug' => 'empresa-transaccional',
        'razon_social' => 'Empresa Transaccional S.A.C.',
        'ruc' => '20199999901',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $invitacion = InvitacionEmpresa::generarPara(
        empresa: $empresa,
        email: 'gestor.audit@empresa.pe',
        nombre: 'Gestor Auditado',
        rol: RolEmpresa::GestorReclamaciones,
        horasExpiracion: 72
    );

    $conteoLogsAntes = AuditoriaLog::count();

    $response = $this->post(route('invitacion.store', ['token' => $invitacion->token]), [
        'name' => 'Gestor Auditado Oficial',
        'password' => 'Password123456!',
        'password_confirmation' => 'Password123456!',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    // Verifica que la invitación quedó marcada como aceptada
    expect($invitacion->fresh()->isAccepted())->toBeTrue();

    // Verifica que se generó el log en AuditoriaService
    expect(AuditoriaLog::count())->toBe($conteoLogsAntes + 1);

    $ultimoLog = AuditoriaLog::latest('id')->first();
    expect($ultimoLog->accion)->toBe('invitacion_aceptada')
        ->and($ultimoLog->modulo)->toBe('usuarios')
        ->and($ultimoLog->empresa_id)->toBe($empresa->id)
        ->and($ultimoLog->actor_email)->toBe('gestor.audit@empresa.pe');
});

test('P2-16: datos_consumidor se almacena cifrado en base de datos y se descifra transparentemente en modelo', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Cifrado PII',
        'slug' => 'empresa-cifrado-pii',
        'razon_social' => 'Empresa Cifrado PII S.A.C.',
        'ruc' => '20199999902',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $datosConsumidor = [
        'tipo_documento' => 'dni',
        'numero_documento' => '87654321',
        'nombres' => 'Valeria Secreta',
        'apellidos' => 'Montes De Oca',
        'email' => 'valeria.confidencial@seguridad.pe',
        'telefono' => '999888777',
        'domicilio' => 'Av. Los Incas 999',
    ];

    $reclamacion = Reclamacion::create([
        'empresa_id' => $empresa->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Laptop Gamer',
        'detalle_problema' => 'Falla en placa base',
        'pedido_consumidor' => 'Cambio de equipo',
        'datos_consumidor' => $datosConsumidor,
        'datos_legales_snapshot' => ['razon_social' => $empresa->razon_social, 'ruc' => $empresa->ruc],
        'token_consulta' => 'tokencifrado12345678901234567890123456',
    ]);

    // 1. Verificación directa en base de datos en crudo (raw SQL): debe ser ciphertext, NO contener texto plano
    $rawDbValue = DB::table('reclamaciones')->where('id', $reclamacion->id)->value('datos_consumidor');
    expect($rawDbValue)->not->toBeNull();
    expect($rawDbValue)->not->toContain('Valeria Secreta');
    expect($rawDbValue)->not->toContain('87654321');
    expect($rawDbValue)->not->toContain('valeria.confidencial@seguridad.pe');

    // 2. Verificación en Eloquent: descifrado transparente en PHP
    $reclamacionDescifrada = Reclamacion::find($reclamacion->id);
    expect($reclamacionDescifrada->datos_consumidor)->toBeArray()
        ->and($reclamacionDescifrada->datos_consumidor['nombres'])->toBe('Valeria Secreta')
        ->and($reclamacionDescifrada->datos_consumidor['numero_documento'])->toBe('87654321')
        ->and($reclamacionDescifrada->datos_consumidor['email'])->toBe('valeria.confidencial@seguridad.pe');
});

test('P2-16: RegistrarReclamacionAction no duplica PII del consumidor en el evento de auditoria inicial', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Sin Duplicidad PII',
        'slug' => 'empresa-sin-duplicidad-pii',
        'razon_social' => 'Empresa Sin Duplicidad PII S.A.C.',
        'ruc' => '20199999903',
        'email' => 'reclamos@empresa3.pe',
        'telefono' => '987654321',
        'direccion' => 'Av. Central 123',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $datosValidados = [
        'tipo' => 'reclamo',
        'tipo_bien' => 'producto',
        'descripcion_bien' => 'Monitor 4K',
        'monto_reclamado' => 1200.00,
        'moneda' => 'PEN',
        'numero_comprobante' => 'F001-44321',
        'fecha_contratacion' => '2026-05-10',
        'detalle_problema' => 'Pixeles muertos en panel',
        'pedido_consumidor' => 'Reemplazo de producto',
        'tipo_documento' => 'dni',
        'numero_documento' => '71234567',
        'nombres' => 'Gabriel',
        'apellidos' => 'Torres Privado',
        'email' => 'gabriel.privado@correo.pe',
        'telefono' => '944332211',
        'direccion' => 'Calle Los Nogales 456',
        'es_menor_edad' => false,
    ];

    $action = app(RegistrarReclamacionAction::class);
    $reclamacion = $action->ejecutar(
        empresa: $empresa,
        datos: $datosValidados,
        ip: '192.168.1.50',
        userAgent: 'Pest Testing Agent'
    );

    // Verificar que el evento inicial NO contiene la clave 'consumidor' duplicando PII
    $eventoInicial = ReclamacionEvento::where('reclamacion_id', $reclamacion->id)
        ->where('accion', 'presentacion_inicial')
        ->first();

    expect($eventoInicial)->not->toBeNull();
    expect($eventoInicial->datos_despues)->not->toHaveKey('consumidor');
    expect($eventoInicial->datos_despues)->toHaveKey('numero_hoja');
    expect($eventoInicial->datos_despues)->toHaveKey('detalle_problema');
});

test('P2-16: ConsultarEstadoReclamacionAction funciona con datos_consumidor cifrado', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Consulta Cifrada',
        'slug' => 'empresa-consulta-cifrada',
        'razon_social' => 'Empresa Consulta Cifrada S.A.C.',
        'ruc' => '20199999904',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $reclamacion = Reclamacion::create([
        'empresa_id' => $empresa->id,
        'numero_hoja' => '00000005-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Mantenimiento Preventivo',
        'detalle_problema' => 'Servicio incompleto',
        'pedido_consumidor' => 'Devolución de importe',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '76543210',
            'nombres' => 'Clara',
            'apellidos' => 'Fuentes',
            'email' => 'clara@correo.pe',
        ],
        'datos_legales_snapshot' => ['razon_social' => $empresa->razon_social, 'ruc' => $empresa->ruc],
        'token_consulta' => 'tokenseguroconsulta12345678901234567890',
    ]);

    $action = app(ConsultarEstadoReclamacionAction::class);

    // Consulta con credenciales correctas
    $resultado = $action->ejecutar($empresa, 'dni', '76543210', '00000005-2026');
    expect($resultado)->not->toBeNull();
    expect($resultado['numero_hoja'])->toBe('00000005-2026');

    // Consulta con documento incorrecto
    $resultadoFallo = $action->ejecutar($empresa, 'dni', '00000000', '00000005-2026');
    expect($resultadoFallo)->toBeNull();
});
