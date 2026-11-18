<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->superadmin = User::create([
        'name' => 'Superadmin Global',
        'email' => 'superadmin.est@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);
});

test('P3-02: Admin/EmpresaController::store crea empresa, sede y auditoria de forma transaccional', function () {
    $this->actingAs($this->superadmin);

    $conteoLogsAntes = AuditoriaLog::count();

    $response = $this->post(route('admin.empresas.store'), [
        'name' => 'Empresa Transaccional Sedes',
        'razon_social' => 'EMPRESA TRANSACCIONAL SEDES S.A.C.',
        'ruc' => '20776655441',
        'slug' => 'empresa-transaccional-sedes',
        'direccion' => 'Av. Javier Prado Este 1000',
        'email' => 'sedes@empresa.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);

    $response->assertRedirect(route('admin.empresas.index'));

    $empresa = Empresa::where('slug', 'empresa-transaccional-sedes')->first();
    expect($empresa)->not->toBeNull();

    // Sede Principal creada atómicamente
    $sedePrincipal = $empresa->establecimientos()->where('es_principal', true)->first();
    expect($sedePrincipal)->not->toBeNull()
        ->and($sedePrincipal->codigo)->toBe('SEDE-01')
        ->and($sedePrincipal->nombre)->toBe('Sede Principal')
        ->and($sedePrincipal->direccion)->toBe('Av. Javier Prado Este 1000');

    // Registro de auditoría generado en la misma transacción
    expect(AuditoriaLog::count())->toBe($conteoLogsAntes + 1);
    $log = AuditoriaLog::latest('id')->first();
    expect($log->accion)->toBe('empresa_creada')
        ->and($log->empresa_id)->toBe($empresa->id);
});

test('P3-03: no se permite desmarcar la sede principal si no hay otra designada', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Monosede',
        'slug' => 'empresa-monosede',
        'razon_social' => 'EMPRESA MONOSEDE S.A.C.',
        'ruc' => '20776655442',
        'direccion' => 'Av. Arequipa 500',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $sedePrincipal = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-01',
        'nombre' => 'Sede Central',
        'direccion' => 'Av. Arequipa 500',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->withSession(['current_tenant_id' => $empresa->id]);

    // Intento de desmarcar es_principal sin que exista otra sede principal
    $response = $this->put(route('empresa.establecimientos.update', $sedePrincipal), [
        'nombre' => 'Sede Central Modificada',
        'direccion' => 'Av. Arequipa 500',
        'es_principal' => false,
        'activo' => true,
    ]);

    $response->assertSessionHasErrors(['es_principal']);
    expect($sedePrincipal->fresh()->es_principal)->toBeTrue();
});

test('P3-03: no se permite eliminar el unico establecimiento de la empresa', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Una Sede',
        'slug' => 'empresa-una-sede',
        'razon_social' => 'EMPRESA UNA SEDE S.A.C.',
        'ruc' => '20776655443',
        'direccion' => 'Av. Pardo 200',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $sedeUnica = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-01',
        'nombre' => 'Única Sede',
        'direccion' => 'Av. Pardo 200',
        'es_principal' => true,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->delete(route('empresa.establecimientos.destroy', $sedeUnica))
        ->assertSessionHasErrors(['error']);

    expect(Establecimiento::where('id', $sedeUnica->id)->exists())->toBeTrue();
});

test('P3-03: al eliminar la sede principal habiendo otras sedes, se reasigna automaticamente la principal y se audita', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Multisede Reasigna',
        'slug' => 'empresa-multisede-reasigna',
        'razon_social' => 'EMPRESA MULTISEDE REASIGNA S.A.C.',
        'ruc' => '20776655444',
        'direccion' => 'Av. Larco 100',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $admin = User::factory()->create();
    $empresa->usuarios()->attach($admin->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $sedePrincipal = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-01',
        'nombre' => 'Sede Larco (Principal)',
        'direccion' => 'Av. Larco 100',
        'es_principal' => true,
        'activo' => true,
    ]);

    $sedeSecundaria = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'SEDE-02',
        'nombre' => 'Sede Benavides',
        'direccion' => 'Av. Benavides 200',
        'es_principal' => false,
        'activo' => true,
    ]);

    $conteoLogsAntes = AuditoriaLog::count();

    $this->actingAs($admin)
        ->withSession(['current_tenant_id' => $empresa->id])
        ->delete(route('empresa.establecimientos.destroy', $sedePrincipal))
        ->assertRedirect()
        ->assertSessionHas('success');

    // La sede principal anterior fue eliminada
    expect(Establecimiento::where('id', $sedePrincipal->id)->exists())->toBeFalse();

    // La sede secundaria fue promovida automáticamente a principal
    expect($sedeSecundaria->fresh()->es_principal)->toBeTrue()
        ->and($sedeSecundaria->fresh()->activo)->toBeTrue();

    // Se registró el evento en AuditoriaService
    expect(AuditoriaLog::count())->toBe($conteoLogsAntes + 1);
    $log = AuditoriaLog::latest('id')->first();
    expect($log->accion)->toBe('establecimiento_eliminado')
        ->and($log->modulo)->toBe('establecimientos')
        ->and($log->empresa_id)->toBe($empresa->id);
});

test('P3-03: operaciones en establecimientos de tenants ajenos son bloqueadas con 403', function () {
    $empresaA = Empresa::create([
        'name' => 'Empresa A',
        'slug' => 'empresa-a-idor',
        'razon_social' => 'EMPRESA A S.A.C.',
        'ruc' => '20776655445',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $empresaB = Empresa::create([
        'name' => 'Empresa B',
        'slug' => 'empresa-b-idor',
        'razon_social' => 'EMPRESA B S.A.C.',
        'ruc' => '20776655446',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $adminA = User::factory()->create();
    $empresaA->usuarios()->attach($adminA->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);

    $sedeB = Establecimiento::create([
        'empresa_id' => $empresaB->id,
        'codigo' => 'SEDE-B',
        'nombre' => 'Sede de B',
        'direccion' => 'Calle Ajena 123',
        'es_principal' => true,
        'activo' => true,
    ]);

    // Admin de Empresa A intenta manipular sede de Empresa B (bloqueado por aislamiento multitenant)
    $responsePut = $this->actingAs($adminA)
        ->withSession(['current_tenant_id' => $empresaA->id])
        ->put(route('empresa.establecimientos.update', $sedeB), [
            'nombre' => 'Hack Sede B',
            'direccion' => 'Calle Hack 123',
        ]);
    expect(in_array($responsePut->status(), [403, 404], true))->toBeTrue();

    $responseDel = $this->actingAs($adminA)
        ->withSession(['current_tenant_id' => $empresaA->id])
        ->delete(route('empresa.establecimientos.destroy', $sedeB));
    expect(in_array($responseDel->status(), [403, 404], true))->toBeTrue();
});
