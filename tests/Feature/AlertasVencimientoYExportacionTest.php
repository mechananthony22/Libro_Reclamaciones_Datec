<?php

use App\Console\Commands\VerificarVencimientosReclamacionesCommand;
use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Mail\AlertaVencimientoMailable;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->empresaA = Empresa::create([
        'name' => 'Comercial Andina S.A.C.',
        'razon_social' => 'COMERCIAL ANDINA S.A.C.',
        'ruc' => '20556677889',
        'direccion' => 'Av. Rivera Navarrete 501, San Isidro, Lima',
        'email' => 'contacto@andina.pe',
        'slug' => 'comercial-andina',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Servicios Globales EIRL',
        'razon_social' => 'SERVICIOS GLOBALES E.I.R.L.',
        'ruc' => '20998877665',
        'direccion' => 'Av. Larco 400, Miraflores, Lima',
        'email' => 'info@globales.pe',
        'slug' => 'servicios-globales',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->adminA = User::create([
        'name' => 'Administradora Andina',
        'email' => 'admin@andina.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => false,
    ]);

    $this->adminA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->gestorA = User::create([
        'name' => 'Gestor Operativo',
        'email' => 'gestor@andina.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => false,
    ]);

    $this->gestorA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);
});

test('verifica vencimientos y notifica por correo a los administradores cuando el plazo esta por expirar', function () {
    Mail::fake();

    $reclamacionUrgente = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000010-2026',
        'fecha_hora' => now()->subDays(13),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Televisor Smart 55 pulgadas',
        'monto_reclamado' => 1899.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'Falla en panel LED tras encendido.',
        'pedido_consumidor' => 'Cambio de equipo.',
        'estado' => EstadoReclamacion::Pendiente,
        'asignado_a' => $this->gestorA->id,
        'vence_en' => Carbon::now('America/Lima')->addDays(2),
        'datos_consumidor' => [
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza Ramos',
            'tipo_documento' => 'DNI',
            'numero_documento' => '44556677',
            'email' => 'carlos.mendoza@gmail.com',
        ],
    ]);

    Artisan::call(VerificarVencimientosReclamacionesCommand::class, ['--dias' => 3]);

    Mail::assertSent(AlertaVencimientoMailable::class, function ($mail) use ($reclamacionUrgente) {
        return $mail->reclamacion->id === $reclamacionUrgente->id
            && $mail->hasTo('admin@andina.pe')
            && $mail->hasTo('gestor@andina.pe');
    });

    $this->assertDatabaseHas('reclamacion_eventos', [
        'empresa_id' => $this->empresaA->id,
        'reclamacion_id' => $reclamacionUrgente->id,
        'accion' => 'alerta_vencimiento_enviada',
    ]);
});

test('notifica como expediente vencido cuando la fecha limite legal ya fue superada', function () {
    Mail::fake();

    $reclamacionVencida = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000011-2026',
        'fecha_hora' => now()->subDays(20),
        'tipo' => TipoReclamacion::Queja,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Servicio de entrega a domicilio',
        'monto_reclamado' => null,
        'moneda' => 'PEN',
        'detalle_problema' => 'Retraso de más de una semana.',
        'pedido_consumidor' => 'Disculpas formales.',
        'estado' => EstadoReclamacion::EnTramite,
        'asignado_a' => null,
        'vence_en' => Carbon::now('America/Lima')->subDays(2),
        'datos_consumidor' => [
            'nombres' => 'Rosa',
            'apellidos' => 'Guerrero Silva',
            'tipo_documento' => 'DNI',
            'numero_documento' => '71223344',
            'email' => 'rosa.guerrero@gmail.com',
        ],
    ]);

    Artisan::call(VerificarVencimientosReclamacionesCommand::class, ['--dias' => 3]);

    Mail::assertSent(AlertaVencimientoMailable::class, function ($mail) use ($reclamacionVencida) {
        return $mail->reclamacion->id === $reclamacionVencida->id
            && ($mail->semaforo['vencido'] ?? false) === true;
    });
});

test('no envia alertas a reclamaciones atendidas o cerradas ni a expedientes con plazo amplio', function () {
    Mail::fake();

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000012-2026',
        'fecha_hora' => now()->subDay(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Celular',
        'estado' => EstadoReclamacion::Atendida,
        'vence_en' => Carbon::now('America/Lima')->addDays(1),
        'datos_consumidor' => ['nombres' => 'Test', 'apellidos' => 'User'],
    ]);

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000013-2026',
        'fecha_hora' => now()->subDay(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Impresora',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDays(12),
        'datos_consumidor' => ['nombres' => 'Test 2', 'apellidos' => 'User 2'],
    ]);

    Artisan::call(VerificarVencimientosReclamacionesCommand::class, ['--dias' => 3]);

    Mail::assertNothingSent();
});

test('no reenvia alertas duplicadas el mismo dia calendario para la misma reclamacion', function () {
    Mail::fake();

    $rec = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000014-2026',
        'fecha_hora' => now()->subDays(14),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Horno Microondas',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDay(),
        'datos_consumidor' => ['nombres' => 'Ana', 'apellidos' => 'Perez'],
    ]);

    Artisan::call(VerificarVencimientosReclamacionesCommand::class, ['--dias' => 3]);
    Mail::assertSentCount(1);

    // Segunda ejecución en el mismo ciclo diario
    Artisan::call(VerificarVencimientosReclamacionesCommand::class, ['--dias' => 3]);
    Mail::assertSentCount(1);
});

test('exporta expedientes en formato csv respetando el aislamiento multitenant y auditando la descarga', function () {
    $recA1 = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000021-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Colchón King Size',
        'monto_reclamado' => 1200.00,
        'moneda' => 'PEN',
        'detalle_problema' => 'Defecto de costura lateral.',
        'pedido_consumidor' => 'Cambio de producto.',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDays(13),
        'datos_consumidor' => [
            'nombres' => 'Julio',
            'apellidos' => 'Casas',
            'tipo_documento' => 'DNI',
            'numero_documento' => '40998877',
            'email' => 'julio.casas@correo.pe',
        ],
    ]);

    $recB1 = Reclamacion::create([
        'empresa_id' => $this->empresaB->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'SERVICIOS GLOBALES E.I.R.L.',
            'ruc' => '20998877665',
        ],
        'numero_hoja' => '00000099-2026',
        'fecha_hora' => now()->subDays(1),
        'tipo' => TipoReclamacion::Queja,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Atención en caja',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDays(14),
        'datos_consumidor' => [
            'nombres' => 'Patricia',
            'apellidos' => 'Vargas',
            'tipo_documento' => 'DNI',
            'numero_documento' => '10203040',
        ],
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.expedientes.exportar', ['formato' => 'csv']));

    $response->assertOk();
    $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

    $contenido = $response->streamedContent();

    // Debe contener el reclamo de la empresa A
    expect($contenido)->toContain('00000021-2026');
    expect($contenido)->toContain('Julio');
    expect($contenido)->toContain('40998877');

    // NUNCA debe contener el reclamo de la empresa B
    expect($contenido)->not->toContain('00000099-2026');
    expect($contenido)->not->toContain('Patricia');

    // Debe existir registro de auditoría en los eventos del expediente exportado
    $this->assertDatabaseHas('reclamacion_eventos', [
        'empresa_id' => $this->empresaA->id,
        'reclamacion_id' => $recA1->id,
        'accion' => 'expediente_exportado',
        'actor_id' => $this->adminA->id,
    ]);

    $this->assertDatabaseMissing('reclamacion_eventos', [
        'reclamacion_id' => $recB1->id,
        'accion' => 'expediente_exportado',
    ]);
});

test('exporta en formato csv aplicando filtros de tipo y estado', function () {
    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000030-2026',
        'fecha_hora' => now()->subDays(3),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Bicicleta Montañera',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Mario', 'apellidos' => 'Bros'],
    ]);

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000031-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Queja,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Atención al cliente',
        'estado' => EstadoReclamacion::EnTramite,
        'datos_consumidor' => ['nombres' => 'Luigi', 'apellidos' => 'Verde'],
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.expedientes.exportar', [
            'formato' => 'csv',
            'tipo' => 'queja',
        ]));

    $response->assertOk();
    $contenido = $response->streamedContent();

    expect($contenido)->toContain('00000031-2026');
    expect($contenido)->toContain('Luigi');
    expect($contenido)->not->toContain('00000030-2026');
    expect($contenido)->not->toContain('Mario');
});

test('exporta el reporte oficial de expedientes en formato pdf', function () {
    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'datos_legales_snapshot' => [
            'razon_social' => 'COMERCIAL ANDINA S.A.C.',
            'ruc' => '20556677889',
        ],
        'numero_hoja' => '00000040-2026',
        'fecha_hora' => now()->subDays(1),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Refrigeradora No Frost',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDays(14),
        'datos_consumidor' => [
            'nombres' => 'Diana',
            'apellidos' => 'Prince',
            'tipo_documento' => 'DNI',
            'numero_documento' => '00112233',
        ],
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.expedientes.exportar', ['formato' => 'pdf']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="reporte_reclamaciones_comercial-andina_');
});
