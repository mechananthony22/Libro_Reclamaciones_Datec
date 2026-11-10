<?php

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Models\SoporteAutorizacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-06 10:00:00'));

    $this->empresaA = Empresa::create([
        'name' => 'Empresa Alfa SAC',
        'razon_social' => 'EMPRESA ALFA S.A.C.',
        'ruc' => '20100000001',
        'direccion' => 'Av. Principal 100, Lima',
        'email' => 'legal@alfa.pe',
        'slug' => 'empresa-alfa',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Empresa Beta SAC',
        'razon_social' => 'EMPRESA BETA S.A.C.',
        'ruc' => '20200000002',
        'direccion' => 'Av. Secundaria 200, Lima',
        'email' => 'legal@beta.pe',
        'slug' => 'empresa-beta',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->adminAlfa = User::create([
        'name' => 'Admin Alfa',
        'email' => 'admin@alfa.pe',
        'password' => Hash::make('password'),
        'is_superadmin' => false,
    ]);
    $this->adminAlfa->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Datec',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('password'),
        'is_superadmin' => true,
    ]);

    $this->establecimientoA = Establecimiento::create([
        'empresa_id' => $this->empresaA->id,
        'nombre' => 'Sede Central Alfa',
        'codigo' => 'ALFA-01',
        'direccion' => 'Av. Principal 100, Lima',
        'activo' => true,
    ]);

    $this->reclamacionA = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->establecimientoA->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => Carbon::parse('2026-11-01 10:00:00'),
        'vence_en' => Carbon::parse('2026-11-20 18:00:00'),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Servicio de consultoría',
        'estado' => EstadoReclamacion::Pendiente,
        'monto_reclamado' => 150.00,
        'pedido_consumidor' => 'Devolución de importe',
        'detalle_reclamacion' => 'Demora injustificada en entrega',
        'datos_consumidor' => [
            'tipo_documento' => 'DNI',
            'numero_documento' => '44556677',
            'nombres' => 'Consumidor',
            'apellidos' => 'Prueba',
            'email' => 'consumidor@ejemplo.com',
            'telefono' => '999888777',
            'direccion' => 'Calle Falsa 123',
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA ALFA S.A.C.',
            'ruc' => '20100000001',
            'direccion' => 'Av. Principal 100, Lima',
        ],
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('superadministrador no puede acceder a expedientes de una empresa sin autorizacion activa de soporte', function () {
    $this->actingAs($this->superadmin)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get('/expedientes')
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get("/expedientes/{$this->reclamacionA->id}")
        ->assertForbidden();

    $this->actingAs($this->superadmin)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get('/expedientes/exportar?formato=csv')
        ->assertForbidden();
});

test('superadministrador accede a expedientes cuando existe autorizacion delegada vigente y se registra en auditoria', function () {
    SoporteAutorizacion::create([
        'empresa_id' => $this->empresaA->id,
        'codigo_ticket' => 'TCK-20261106-0001',
        'titulo' => 'Asistencia en configuración de constancia',
        'motivo' => 'Diagnóstico técnico del motor PDF',
        'solicitado_por_id' => $this->superadmin->id,
        'autorizado_por_id' => $this->adminAlfa->id,
        'estado' => 'autorizado',
        'horas_vigencia' => 24,
        'autorizado_en' => Carbon::now()->subHour(),
        'valido_hasta' => Carbon::now()->addHours(23),
    ]);

    $response = $this->actingAs($this->superadmin)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get('/expedientes');

    $response->assertOk();

    expect(AuditoriaLog::where('accion', 'acceso_delegado_soporte')
        ->where('empresa_id', $this->empresaA->id)
        ->exists())->toBeTrue();
});

test('superadministrador puede solicitar autorizacion delegada a empresa cliente desde panel de soporte', function () {
    $response = $this->actingAs($this->superadmin)
        ->post('/admin/soporte/solicitar', [
            'empresa_id' => $this->empresaA->id,
            'titulo' => 'Revisión técnica de envío de constancias',
            'motivo' => 'Verificar fallo de conexión al servidor SMTP',
            'horas_vigencia' => 48,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('soporte_autorizaciones', [
        'empresa_id' => $this->empresaA->id,
        'titulo' => 'Revisión técnica de envío de constancias',
        'estado' => 'pendiente',
        'horas_vigencia' => 48,
    ]);
});

test('empresa puede autorizar y revocar acceso delegado de soporte tecnico', function () {
    $ticket = SoporteAutorizacion::create([
        'empresa_id' => $this->empresaA->id,
        'codigo_ticket' => 'TCK-20261106-0002',
        'titulo' => 'Solicitud pendiente de asistencia',
        'motivo' => 'Ayuda en auditoría',
        'solicitado_por_id' => $this->superadmin->id,
        'estado' => 'pendiente',
        'horas_vigencia' => 24,
    ]);

    // Autorizar acceso
    $this->actingAs($this->adminAlfa)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch("/configuracion/soporte/{$ticket->id}/autorizar", [
            'horas_vigencia' => 48,
        ])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->estado)->toBe('autorizado');
    expect($ticket->isActivo())->toBeTrue();
    expect($ticket->horas_vigencia)->toBe(48);

    // Revocar acceso
    $this->actingAs($this->adminAlfa)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch("/configuracion/soporte/{$ticket->id}/revocar", [
            'motivo_revocacion' => 'Problema resuelto',
        ])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->estado)->toBe('revocado');
    expect($ticket->isActivo())->toBeFalse();
    expect($ticket->motivo_revocacion)->toBe('Problema resuelto');

    // Ahora el superadmin vuelve a estar bloqueado
    $this->actingAs($this->superadmin)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get('/expedientes')
        ->assertForbidden();
});

test('empresa puede configurar correos de notificacion y preferencias de alertas preventivas', function () {
    $response = $this->actingAs($this->adminAlfa)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->put('/configuracion/notificaciones', [
            'emails_notificaciones' => ['alerta1@alfa.pe', 'gerencia@alfa.pe'],
            'notificar_nuevo_reclamo' => true,
            'notificar_vencimiento_preventivo' => true,
            'dias_anticipacion_alerta' => 4,
        ]);

    $response->assertRedirect();

    $this->empresaA->refresh();
    expect($this->empresaA->emails_notificaciones)->toContain('alerta1@alfa.pe');
    expect($this->empresaA->emails_notificaciones)->toContain('gerencia@alfa.pe');
    expect($this->empresaA->dias_anticipacion_alerta)->toBe(4);
    expect($this->empresaA->notificar_vencido)->toBeTrue();
});

test('configuracion de notificaciones de empresa A no afecta a empresa B', function () {
    $this->empresaB->update([
        'emails_notificaciones' => ['operaciones@beta.pe'],
        'dias_anticipacion_alerta' => 2,
    ]);

    $this->actingAs($this->adminAlfa)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->put('/configuracion/notificaciones', [
            'emails_notificaciones' => ['nuevo@alfa.pe'],
            'notificar_nuevo_reclamo' => false,
            'notificar_vencimiento_preventivo' => true,
            'dias_anticipacion_alerta' => 5,
        ]);

    $this->empresaA->refresh();
    $this->empresaB->refresh();

    expect($this->empresaA->emails_notificaciones)->toEqual(['nuevo@alfa.pe']);
    expect($this->empresaA->dias_anticipacion_alerta)->toBe(5);

    expect($this->empresaB->emails_notificaciones)->toEqual(['operaciones@beta.pe']);
    expect($this->empresaB->dias_anticipacion_alerta)->toBe(2);
});

test('alerta de plazo legal vencido permanece obligatoriamente activa ante cualquier actualizacion', function () {
    $this->actingAs($this->adminAlfa)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->put('/configuracion/notificaciones', [
            'emails_notificaciones' => ['legal@alfa.pe'],
            'notificar_nuevo_reclamo' => false,
            'notificar_vencimiento_preventivo' => false,
            'dias_anticipacion_alerta' => 1,
        ]);

    $this->empresaA->refresh();
    expect($this->empresaA->notificar_vencido)->toBeTrue();
});
