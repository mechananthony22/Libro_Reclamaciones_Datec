<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Http\Middleware\ResolveTenantBySlug;
use App\Models\Empresa;
use App\Models\SoporteAutorizacion;
use App\Models\User;
use App\Services\SlugReconfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->empresa = Empresa::create([
        'name' => 'Empresa Retail Test',
        'razon_social' => 'EMPRESA RETAIL TEST S.A.C.',
        'ruc' => '20600000001',
        'direccion' => 'Av. Javier Prado 1234',
        'slug' => 'empresa-retail',
        'email' => 'admin@retail.pe',
        'representante_email' => 'admin@retail.pe',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->superadmin = User::create([
        'name' => 'Superadmin Datec',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);

    $this->usuarioEmpresa = User::create([
        'name' => 'Admin Empresa',
        'email' => 'admin@retail.pe',
        'password' => Hash::make('password123'),
    ]);
    $this->empresa->usuarios()->attach($this->usuarioEmpresa->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);
});

test('P3-04: generarCodigoTicket genera correlativo atómico con ordenamiento descendente de codigo_ticket y bloqueo', function () {
    $hoy = now()->format('Ymd');

    // Simular ticket con número mayor creado
    SoporteAutorizacion::create([
        'empresa_id' => $this->empresa->id,
        'codigo_ticket' => "TCK-{$hoy}-0015",
        'titulo' => 'Ticket 15',
        'motivo' => 'Motivo de prueba',
        'estado' => 'pendiente',
    ]);

    // Debe generar el correlativo inmediatamente posterior al mayor existente (0016)
    $codigo = SoporteAutorizacion::generarCodigoTicket();
    expect($codigo)->toBe("TCK-{$hoy}-0016");

    // Dentro de una transacción activa
    DB::transaction(function () use ($hoy) {
        $codigoTransaccional = SoporteAutorizacion::generarCodigoTicket();
        expect($codigoTransaccional)->toBe("TCK-{$hoy}-0016");
    });
});

test('P3-04: creacion de tickets en Admin y Empresa ejecuta transaccion atomica y registra auditoria', function () {
    Mail::fake();

    // 1. Admin/SoporteController solicitar
    $this->actingAs($this->superadmin);
    $responseAdmin = $this->post(route('admin.soporte.solicitar'), [
        'empresa_id' => $this->empresa->id,
        'titulo' => 'Solicitud diagnóstico',
        'motivo' => 'Revisión técnica de expedientes',
        'horas_vigencia' => 12,
    ]);

    $responseAdmin->assertSessionHas('success');
    $ticketAdmin = SoporteAutorizacion::where('empresa_id', $this->empresa->id)->first();
    expect($ticketAdmin)->not->toBeNull()
        ->and($ticketAdmin->codigo_ticket)->toMatch('/^TCK-\d{8}-\d{4}$/')
        ->and($ticketAdmin->estado)->toBe('pendiente');

    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'solicitud_acceso_delegado_creada',
    ]);

    // 2. Empresa/SoporteController store
    $this->empresa->makeCurrent();
    $this->actingAs($this->usuarioEmpresa)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $responseEmpresa = $this->post(route('empresa.soporte.store'), [
        'titulo' => 'Consulta soporte desde cliente',
        'motivo' => 'Duda sobre exportación de reportes',
        'horas_vigencia' => 24,
        'conceder_acceso_inmediato' => true,
    ]);

    $responseEmpresa->assertSessionHas('success');
    $ticketEmpresa = SoporteAutorizacion::where('empresa_id', $this->empresa->id)
        ->where('id', '!=', $ticketAdmin->id)
        ->first();

    expect($ticketEmpresa)->not->toBeNull()
        ->and($ticketEmpresa->codigo_ticket)->toMatch('/^TCK-\d{8}-\d{4}$/')
        ->and($ticketEmpresa->estado)->toBe('autorizado');

    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'ticket_soporte_creado',
    ]);
});

test('P3-05: ResolveTenantBySlug redirige slug historico limpiamente con una sola evaluacion', function () {
    // Registrar cambio de slug para generar registro historico
    $slugService = app(SlugReconfigurationService::class);
    $slugService->cambiarSlug($this->empresa, 'nuevo-retail-slug', 'Rebranding corporativo', $this->superadmin);

    $middleware = new ResolveTenantBySlug;
    $request = Request::create('/l/empresa-retail/reclamar', 'GET');
    $request->setRouteResolver(function () {
        $route = new class
        {
            public function parameter($name, $default = null)
            {
                return 'empresa-retail';
            }
        };

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toContain('/l/nuevo-retail-slug');
});
