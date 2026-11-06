<?php

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Models\ReclamacionEvento;
use App\Models\User;
use App\Services\PlazoReclamacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->empresaA = Empresa::create([
        'name' => 'Empresa Alfa SAC',
        'razon_social' => 'EMPRESA ALFA S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Javier Prado 100, Lima',
        'slug' => 'empresa-alfa',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Empresa Beta SAC',
        'razon_social' => 'EMPRESA BETA S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Arequipa 500, Lima',
        'slug' => 'empresa-beta',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->sedeA1 = Establecimiento::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'SED-001',
        'nombre' => 'Sede Miraflores',
        'direccion' => 'Calle Las Begonias 123',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->usuarioAdminA = User::create([
        'name' => 'Admin Alfa',
        'email' => 'admin@alfa.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $this->usuarioAdminA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->gestorA = User::create([
        'name' => 'Gestor Soporte Alfa',
        'email' => 'soporte@alfa.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $this->gestorA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->usuarioExternoB = User::create([
        'name' => 'Usuario Beta Externo',
        'email' => 'externo@beta.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => false,
    ]);

    $this->usuarioExternoB->empresas()->attach($this->empresaB->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->reclamacionA = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->sedeA1->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Televisor Smart 55 pulgadas',
        'monto_reclamado' => 1899.00,
        'moneda' => 'PEN',
        'numero_comprobante' => 'B001-000456',
        'fecha_contratacion' => now()->subDays(10)->toDateString(),
        'detalle_problema' => 'La pantalla presenta líneas verticales tras 2 días de uso.',
        'pedido_consumidor' => 'Cambio de equipo o nota de crédito.',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '44556677',
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'email' => 'carlos.mendoza@ejemplo.com',
            'telefono' => '998877665',
            'direccion' => 'Av. Pardo 456',
            'es_menor_edad' => false,
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA ALFA S.A.C.',
            'ruc' => '20123456789',
            'direccion' => 'Av. Javier Prado 100, Lima',
        ],
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => now()->addWeekdays(15)->toDateString(),
        'token_consulta' => Str::random(40),
    ]);

    // Reclamación de Empresa Beta
    $this->reclamacionB = Reclamacion::create([
        'empresa_id' => $this->empresaB->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now(),
        'tipo' => TipoReclamacion::Queja,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Servicio Técnico',
        'detalle_problema' => 'Mala atención del personal.',
        'pedido_consumidor' => 'Capacitación al personal.',
        'datos_consumidor' => [
            'tipo_documento' => 'dni',
            'numero_documento' => '11223344',
            'nombres' => 'Ana',
            'apellidos' => 'Gomez',
            'email' => 'ana@beta.pe',
            'telefono' => '911223344',
            'direccion' => 'Calle Sur 789',
            'es_menor_edad' => false,
        ],
        'datos_legales_snapshot' => [
            'razon_social' => 'EMPRESA BETA S.A.C.',
            'ruc' => '20987654321',
            'direccion' => 'Av. Arequipa 500, Lima',
        ],
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => now()->addWeekdays(15)->toDateString(),
        'token_consulta' => Str::random(40),
    ]);
});

test('un administrador autenticado puede ver la bandeja de expedientes de su empresa', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Expedientes/Index')
        ->has('expedientes.data', 1)
        ->where('expedientes.data.0.numero_hoja', '00000001-2026')
        ->where('expedientes.data.0.consumidor_nombre', 'Carlos Mendoza')
        ->has('metricas')
        ->has('filtros')
    );
});

test('la bandeja aplica aislamiento estricto y no muestra expedientes de otra empresa', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index'));

    $response->assertOk();
    $expedientes = $response->viewData('page')['props']['expedientes']['data'];

    // Solo debe contener el reclamo de Empresa Alfa
    expect($expedientes)->toHaveCount(1)
        ->and($expedientes[0]['consumidor_nombre'])->toBe('Carlos Mendoza')
        ->and(collect($expedientes)->pluck('id'))->not->toContain($this->reclamacionB->id);
});

test('se puede filtrar expedientes por termino de busqueda de consumidor y hoja', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index', ['buscar' => 'Mendoza']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('expedientes.data', 1)
        ->where('expedientes.data.0.numero_hoja', '00000001-2026')
    );

    // Búsqueda que no coincide
    $responseVacio = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index', ['buscar' => 'Inexistente999']));

    $responseVacio->assertOk();
    $responseVacio->assertInertia(fn ($page) => $page
        ->has('expedientes.data', 0)
    );
});

test('se puede filtrar por clasificacion de tipo reclamo o queja', function () {
    $responseReclamo = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index', ['tipo' => 'reclamo']));

    $responseReclamo->assertOk();
    $responseReclamo->assertInertia(fn ($page) => $page
        ->has('expedientes.data', 1)
    );

    $responseQueja = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.index', ['tipo' => 'queja']));

    $responseQueja->assertOk();
    $responseQueja->assertInertia(fn ($page) => $page
        ->has('expedientes.data', 0)
    );
});

test('se puede ver la ficha de reclamacion con datos de origen de solo lectura y bitacora', function () {
    // Registrar evento de prueba en el expediente
    ReclamacionEvento::create([
        'empresa_id' => $this->empresaA->id,
        'reclamacion_id' => $this->reclamacionA->id,
        'actor_tipo' => 'sistema',
        'accion' => 'registro_inicial',
        'descripcion' => 'Reclamación presentada en el libro virtual con hoja 00000001-2026.',
        'ip' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.show', ['id' => $this->reclamacionA->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Expedientes/Show')
        ->has('expediente')
        ->where('expediente.numero_hoja', '00000001-2026')
        ->where('expediente.tipo', 'reclamo')
        ->where('expediente.descripcion_bien', 'Televisor Smart 55 pulgadas')
        ->where('expediente.detalle_problema', 'La pantalla presenta líneas verticales tras 2 días de uso.')
        ->has('expediente.eventos', 1)
        ->has('expediente.semaforo')
    );
});

test('no es posible visualizar la ficha de un expediente perteneciente a otra empresa', function () {
    // Usuario de Empresa A intentando ingresar a la ficha de Empresa B
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.show', ['id' => $this->reclamacionB->id]));

    $response->assertNotFound();
});

test('el servicio de plazo legal calcula correctamente los dias habiles y semaforo', function () {
    $service = new PlazoReclamacionService;

    // Fecha fija: Lunes 2 de Noviembre 2026
    $inicio = Carbon::create(2026, 11, 2, 9, 0, 0, 'America/Lima');
    $vencimiento = $service->calcularFechaVencimiento($inicio, 15);

    // Debe ser posterior y sumar 15 días hábiles descontando fines de semana
    expect($vencimiento->greaterThan($inicio))->toBeTrue()
        ->and($service->esDiaHabil(Carbon::create(2026, 11, 7)))->toBeFalse(); // Sábado

    $semaforo = $service->obtenerEstadoSemaforo($vencimiento, 'pendiente');
    expect($semaforo['vencido'])->toBeFalse()
        ->and($semaforo['color'])->toBeIn(['success', 'amber', 'warning']);
});

test('se puede descargar la constancia en pdf del expediente desde la ficha', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->get(route('empresa.expedientes.pdf', ['id' => $this->reclamacionA->id]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('se puede asignar un responsable habilitado en la misma empresa y se registra auditoria', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch(route('empresa.expedientes.asignar', ['id' => $this->reclamacionA->id]), [
            'usuario_id' => $this->gestorA->id,
            'motivo' => 'Derivado para revisión técnica',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->reclamacionA->refresh();
    expect($this->reclamacionA->asignado_a)->toBe($this->gestorA->id)
        ->and($this->reclamacionA->asignado_en)->not->toBeNull();

    // Comprobar evento de auditoría
    $evento = ReclamacionEvento::where('reclamacion_id', $this->reclamacionA->id)
        ->where('accion', 'asignacion_responsable')
        ->first();

    expect($evento)->not->toBeNull()
        ->and($evento->descripcion)->toContain('Gestor Soporte Alfa')
        ->and($evento->descripcion)->toContain('Derivado para revisión técnica')
        ->and($evento->actor_id)->toBe($this->usuarioAdminA->id);
});

test('no es posible asignar un expediente a un usuario ajeno a la empresa', function () {
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch(route('empresa.expedientes.asignar', ['id' => $this->reclamacionA->id]), [
            'usuario_id' => $this->usuarioExternoB->id,
            'motivo' => 'Intento inválido de asignación',
        ]);

    $response->assertSessionHasErrors(['usuario_id']);

    $this->reclamacionA->refresh();
    expect($this->reclamacionA->asignado_a)->toBeNull();
});

test('se pueden realizar transiciones controladas de estado conservando el historico', function () {
    // 1. Transición de Pendiente -> En trámite
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch(route('empresa.expedientes.estado', ['id' => $this->reclamacionA->id]), [
            'estado' => EstadoReclamacion::EnTramite->value,
            'motivo' => 'Se apertura el análisis con el área de garantías.',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->reclamacionA->refresh();
    expect($this->reclamacionA->estado)->toBe(EstadoReclamacion::EnTramite);

    // 2. Comprobar que no se borraron fechas de origen ni datos originales
    expect($this->reclamacionA->fecha_hora)->not->toBeNull()
        ->and($this->reclamacionA->detalle_problema)->toBe('La pantalla presenta líneas verticales tras 2 días de uso.');

    // 3. Comprobar evento de auditoría de estado
    $evento = ReclamacionEvento::where('reclamacion_id', $this->reclamacionA->id)
        ->where('accion', 'cambio_estado')
        ->first();

    expect($evento)->not->toBeNull()
        ->and($evento->descripcion)->toContain('Pendiente / Recibido')
        ->and($evento->descripcion)->toContain('En trámite / En revisión')
        ->and($evento->descripcion)->toContain('Se apertura el análisis con el área de garantías.');
});

test('se rechazan transiciones de estado no permitidas por la maquina de estados', function () {
    // Intento de saltar directamente de Pendiente a Cerrada
    $response = $this->actingAs($this->usuarioAdminA)
        ->withSession(['current_tenant_id' => $this->empresaA->id])
        ->patch(route('empresa.expedientes.estado', ['id' => $this->reclamacionA->id]), [
            'estado' => EstadoReclamacion::Cerrada->value,
            'motivo' => 'Cierre forzado indebido',
        ]);

    $response->assertSessionHasErrors(['estado']);

    $this->reclamacionA->refresh();
    expect($this->reclamacionA->estado)->toBe(EstadoReclamacion::Pendiente);
});
