<?php

use App\Console\Commands\VerificarSaludPlataformaCommand;
use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Mail\AlertaOperativaDatecMailable;
use App\Mail\RespuestaAccesoDelegadoMailable;
use App\Mail\SolicitudAccesoDelegadoMailable;
use App\Models\Empresa;
use App\Models\PlataformaAlerta;
use App\Models\SoporteAutorizacion;
use App\Models\User;
use App\Services\PlataformaSaludService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->empresa = Empresa::create([
        'name' => 'Empresa Retail SAC',
        'razon_social' => 'EMPRESA RETAIL S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Test 123',
        'slug' => 'empresa-retail',
        'email' => 'admin@retail.pe',
        'representante_email' => 'rep@retail.pe',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->usuario = User::create([
        'name' => 'Admin Empresa',
        'email' => 'admin@retail.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);
    $this->usuario->empresas()->attach($this->empresa->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Datec',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);
});

test('P2-09: generarCodigoTicket genera correlativos secuenciales basados en el maximo del dia sin colisiones', function () {
    $hoy = now()->format('Ymd');

    // Crear tickets existentes
    SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => "TCK-{$hoy}-0005",
        'titulo' => 'Ticket 5',
        'motivo' => 'Motivo 5',
        'estado' => 'finalizado',
    ]);

    // El siguiente código debe ser 0006 aunque solo haya 1 ticket en la BD
    $nuevoCodigo = SoporteAutorizacion::generarCodigoTicket();
    expect($nuevoCodigo)->toBe("TCK-{$hoy}-0006");
});

test('P2-09: Superadmin solicitar acceso delegado envia mailable a la empresa', function () {
    Mail::fake();

    $this->actingAs($this->superadmin);

    $response = $this->post(route('admin.soporte.solicitar'), [
        'empresa_id' => $this->empresa->id,
        'titulo' => 'Revisión técnica de expedientes',
        'motivo' => 'Se requiere acceso para diagnóstico de indexación.',
        'horas_vigencia' => 24,
    ]);

    $response->assertSessionHas('success');

    $ticket = SoporteAutorizacion::where('empresa_id', $this->empresa->id)->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->estado)->toBe('pendiente');

    Mail::assertSent(SolicitudAccesoDelegadoMailable::class, function ($mail) {
        return $mail->ticket->empresa_id === $this->empresa->id
            && $mail->hasTo('admin@retail.pe');
    });
});

test('P2-09: Empresa puede rechazar solicitud de soporte enviando mailable de respuesta a Datec', function () {
    Mail::fake();

    $ticket = SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => SoporteAutorizacion::generarCodigoTicket(),
        'titulo' => 'Diagnóstico de logs',
        'motivo' => 'Revisión preventiva',
        'solicitado_por_id' => $this->superadmin->id,
        'estado' => 'pendiente',
        'horas_vigencia' => 12,
    ]);

    $this->empresa->makeCurrent();
    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $response = $this->patch(route('empresa.soporte.rechazar', $ticket->id), [
        'motivo' => 'No autorizamos acceso en horario de alta demanda comercial.',
    ]);

    $response->assertSessionHas('info');

    $ticket->refresh();
    expect($ticket->estado)->toBe('rechazado')
        ->and($ticket->motivo_revocacion)->toBe('No autorizamos acceso en horario de alta demanda comercial.');

    Mail::assertSent(RespuestaAccesoDelegadoMailable::class, function ($mail) {
        return $mail->accion === 'rechazado'
            && $mail->hasTo($this->superadmin->email);
    });
});

test('P2-09: Empresa autorizar acceso delegado envia mailable de confirmacion a Datec', function () {
    Mail::fake();

    $ticket = SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => SoporteAutorizacion::generarCodigoTicket(),
        'titulo' => 'Atención de incidencia',
        'motivo' => 'Revisión técnica solicitada',
        'solicitado_por_id' => $this->superadmin->id,
        'estado' => 'pendiente',
        'horas_vigencia' => 24,
    ]);

    $this->empresa->makeCurrent();
    $this->actingAs($this->usuario)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $response = $this->patch(route('empresa.soporte.autorizar', $ticket->id), [
        'horas_vigencia' => 24,
    ]);

    $response->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->estado)->toBe('autorizado')
        ->and($ticket->isActivo())->toBeTrue();

    Mail::assertSent(RespuestaAccesoDelegadoMailable::class, function ($mail) {
        return $mail->accion === 'autorizado'
            && $mail->hasTo($this->superadmin->email);
    });
});

test('P2-09: expirarVencidos y comando de salud expiran automaticamente autorizaciones vencidas', function () {
    // Ticket vencido
    $ticketVencido = SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => 'TCK-20261101-0001',
        'titulo' => 'Ticket Antiguo',
        'motivo' => 'Mantenimiento',
        'estado' => 'autorizado',
        'horas_vigencia' => 12,
        'autorizado_en' => now()->subDays(2),
        'valido_hasta' => now()->subHours(10),
    ]);

    // Ticket aún vigente
    $ticketVigente = SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => 'TCK-20261116-0002',
        'titulo' => 'Ticket Activo',
        'motivo' => 'Revisión en curso',
        'estado' => 'autorizado',
        'horas_vigencia' => 24,
        'autorizado_en' => now()->subHour(),
        'valido_hasta' => now()->addHours(23),
    ]);

    $this->artisan(VerificarSaludPlataformaCommand::class)->assertSuccessful();

    expect($ticketVencido->fresh()->estado)->toBe('expirado')
        ->and($ticketVigente->fresh()->estado)->toBe('autorizado');
});

test('P2-10: PlataformaSaludService despacha correo de alerta formal a Datec ante anomalia', function () {
    Mail::fake();

    $this->empresa->update(['en_contingencia' => true]);

    $saludService = app(PlataformaSaludService::class);
    $diagnostico = $saludService->evaluarUmbralesYAlertar();

    expect($diagnostico['estado_global'])->toBeIn(['advertencia', 'critico']);

    $alerta = PlataformaAlerta::where('tipo', 'contingencia_activa')->where('estado', 'activa')->first();
    expect($alerta)->not->toBeNull()
        ->and($alerta->notificado_datec_en)->not->toBeNull();

    Mail::assertSent(AlertaOperativaDatecMailable::class, function ($mail) use ($alerta) {
        return $mail->alerta->id === $alerta->id
            && $mail->hasTo(config('mail.datec_alertas_email', 'soporte@datec.pe'));
    });
});
