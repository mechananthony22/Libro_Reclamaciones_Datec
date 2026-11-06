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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->empresaA = Empresa::create([
        'name' => 'Inversiones Pacífico SAC',
        'razon_social' => 'INVERSIONES PACÍFICO S.A.C.',
        'ruc' => '20112233445',
        'direccion' => 'Av. Benavides 1230, Miraflores, Lima',
        'email' => 'contacto@pacifico.pe',
        'slug' => 'inversiones-pacifico',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->empresaB = Empresa::create([
        'name' => 'Soluciones Norte SAC',
        'razon_social' => 'SOLUCIONES NORTE S.A.C.',
        'ruc' => '20998811223',
        'direccion' => 'Calle Los Nogales 200, Trujillo',
        'email' => 'contacto@norte.pe',
        'slug' => 'soluciones-norte',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $this->adminA = User::create([
        'name' => 'Administradora Pacífico',
        'email' => 'admin@pacifico.pe',
        'password' => Hash::make('secret123'),
        'is_superadmin' => false,
    ]);

    $this->adminA->empresas()->attach($this->empresaA->id, [
        'rol' => RolEmpresa::AdminEmpresa->value,
        'activo' => true,
    ]);

    $this->sedePrincipal = Establecimiento::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'SEDE-01',
        'nombre' => 'Sede Miraflores',
        'direccion' => 'Av. Benavides 1230',
        'departamento' => 'Lima',
        'provincia' => 'Lima',
        'distrito' => 'Miraflores',
        'activo' => true,
    ]);

    $this->sedeSanIsidro = Establecimiento::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'SEDE-02',
        'nombre' => 'Sede San Isidro',
        'direccion' => 'Av. Conquistadores 450',
        'departamento' => 'Lima',
        'provincia' => 'Lima',
        'distrito' => 'San Isidro',
        'activo' => true,
    ]);
});

test('muestra el dashboard con indicadores y metricas correctas para el tenant autenticado', function () {
    $snapshotLegal = [
        'razon_social' => 'INVERSIONES PACÍFICO S.A.C.',
        'ruc' => '20112233445',
        'direccion' => 'Av. Benavides 1230, Miraflores, Lima',
    ];

    // Crear 1 reclamo pendiente en plazo y 1 atendido en empresa A
    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->sedePrincipal->id,
        'numero_hoja' => '00000001-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Monitor 4K',
        'estado' => EstadoReclamacion::Pendiente,
        'vence_en' => Carbon::now('America/Lima')->addDays(12),
        'datos_consumidor' => ['nombres' => 'Juan', 'apellidos' => 'Perez'],
        'datos_legales_snapshot' => $snapshotLegal,
    ]);

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->sedePrincipal->id,
        'numero_hoja' => '00000002-2026',
        'fecha_hora' => now()->subDays(5),
        'tipo' => TipoReclamacion::Queja,
        'tipo_bien' => TipoBien::Servicio,
        'descripcion_bien' => 'Soporte técnico',
        'estado' => EstadoReclamacion::Atendida,
        'vence_en' => Carbon::now('America/Lima')->addDays(9),
        'datos_consumidor' => ['nombres' => 'Maria', 'apellidos' => 'Gomez'],
        'datos_legales_snapshot' => $snapshotLegal,
    ]);

    // Reclamo en empresa B que NUNCA debe computarse en el dashboard de empresa A
    Reclamacion::create([
        'empresa_id' => $this->empresaB->id,
        'numero_hoja' => '00000099-2026',
        'fecha_hora' => now()->subDay(),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Impresora',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Carlos', 'apellidos' => 'B'],
        'datos_legales_snapshot' => [
            'razon_social' => 'SOLUCIONES NORTE S.A.C.',
            'ruc' => '20998811223',
            'direccion' => 'Calle Los Nogales 200, Trujillo',
        ],
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Dashboard/Index')
        ->where('kpis.total', 2)
        ->where('kpis.pendientes', 1)
        ->where('kpis.atendidas', 1)
        ->where('distribucion_tipo.reclamos', 1)
        ->where('distribucion_tipo.quejas', 1)
        ->where('distribucion_bien.productos', 1)
        ->where('distribucion_bien.servicios', 1)
        ->has('semaforo')
        ->has('antiguedad')
        ->has('desglose_sedes')
    );
});

test('filtra los indicadores analiticos del dashboard por sede o establecimiento', function () {
    $snapshotLegal = [
        'razon_social' => 'INVERSIONES PACÍFICO S.A.C.',
        'ruc' => '20112233445',
        'direccion' => 'Av. Benavides 1230, Miraflores, Lima',
    ];

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->sedePrincipal->id,
        'numero_hoja' => '00000010-2026',
        'fecha_hora' => now()->subDays(1),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Teclado',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Lucas', 'apellidos' => 'M'],
        'datos_legales_snapshot' => $snapshotLegal,
    ]);

    Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'establecimiento_id' => $this->sedeSanIsidro->id,
        'numero_hoja' => '00000011-2026',
        'fecha_hora' => now()->subDays(2),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Mouse',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Ana', 'apellidos' => 'L'],
        'datos_legales_snapshot' => $snapshotLegal,
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->get(route('empresa.dashboard', ['establecimiento_id' => $this->sedeSanIsidro->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Empresa/Dashboard/Index')
        ->where('kpis.total', 1)
        ->where('filtros.establecimiento_id', $this->sedeSanIsidro->id)
    );
});

test('bloquea la eliminacion destructiva via peticion http con codigo 403', function () {
    $rec = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '00000020-2026',
        'fecha_hora' => now()->subDays(3),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Smartphone',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Valeria', 'apellidos' => 'R'],
        'datos_legales_snapshot' => [
            'razon_social' => 'INVERSIONES PACÍFICO S.A.C.',
            'ruc' => '20112233445',
            'direccion' => 'Av. Benavides 1230, Miraflores, Lima',
        ],
    ]);

    $this->empresaA->makeCurrent();

    $response = $this->actingAs($this->adminA)
        ->delete(route('empresa.expedientes.destroy', ['id' => $rec->id]));

    $response->assertForbidden();

    // El expediente debe conservarse intacto en base de datos
    $this->assertDatabaseHas('reclamaciones', [
        'id' => $rec->id,
        'numero_hoja' => '00000020-2026',
    ]);
});

test('bloquea el borrado destructivo a nivel del modelo elocuente mediante excepcion de dominio', function () {
    $rec = Reclamacion::create([
        'empresa_id' => $this->empresaA->id,
        'numero_hoja' => '00000021-2026',
        'fecha_hora' => now()->subDays(1),
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Impresora Multifuncional',
        'estado' => EstadoReclamacion::Pendiente,
        'datos_consumidor' => ['nombres' => 'Pedro', 'apellidos' => 'Suarez'],
        'datos_legales_snapshot' => [
            'razon_social' => 'INVERSIONES PACÍFICO S.A.C.',
            'ruc' => '20112233445',
            'direccion' => 'Av. Benavides 1230, Miraflores, Lima',
        ],
    ]);

    expect(fn () => $rec->delete())
        ->toThrow(DomainException::class);

    $this->assertDatabaseHas('reclamaciones', [
        'id' => $rec->id,
        'numero_hoja' => '00000021-2026',
    ]);
});
