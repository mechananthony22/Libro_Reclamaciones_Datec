<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->superadmin = User::create([
        'name' => 'Superadmin Global',
        'email' => 'superadmin@datec.pe',
        'password' => Hash::make('password123'),
        'is_superadmin' => true,
    ]);
});

test('P2-11: busqueda en Admin/EmpresaController funciona de forma portable con el operador like/ilike correspondiente', function () {
    $this->actingAs($this->superadmin);

    $empresa1 = Empresa::create([
        'name' => 'Tecnología Peruana SAC',
        'slug' => 'tecnologia-peruana',
        'razon_social' => 'TECNOLOGÍA PERUANA S.A.C.',
        'ruc' => '20112233445',
        'direccion' => 'Av. Rivera Navarrete 500',
        'email' => 'info@tecperu.pe',
        'telefono' => '999111222',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    $empresa2 = Empresa::create([
        'name' => 'Farmacia Los Andes',
        'slug' => 'farmacia-los-andes',
        'razon_social' => 'FARMACIA LOS ANDES S.A.C.',
        'ruc' => '20998877665',
        'direccion' => 'Calle Real 123',
        'email' => 'contacto@losandes.pe',
        'telefono' => '999333444',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    // Búsqueda por coincidencia de nombre
    $response = $this->get(route('admin.empresas.index', ['buscar' => 'Tecnología']));
    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->component('Admin/Empresas/Index')
            ->has('empresas.data', 1)
            ->where('empresas.data.0.slug', 'tecnologia-peruana');
    });

    // Búsqueda por coincidencia de RUC
    $responseRuc = $this->get(route('admin.empresas.index', ['buscar' => '209988']));
    $responseRuc->assertOk();
    $responseRuc->assertInertia(function ($page) {
        $page->component('Admin/Empresas/Index')
            ->has('empresas.data', 1)
            ->where('empresas.data.0.slug', 'farmacia-los-andes');
    });
});

test('P2-12: LibroReclamacionesController aborta con 503 si la empresa no cumple tieneMinimosLegales', function () {
    // Empresa con RUC, razón social y dirección, pero SIN email y SIN teléfono
    $empresaIncompleta = Empresa::create([
        'name' => 'Empresa Sin Contacto SAC',
        'slug' => 'empresa-sin-contacto',
        'razon_social' => 'EMPRESA SIN CONTACTO S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Sin Contacto 123',
        'email' => null,
        'telefono' => null,
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    expect($empresaIncompleta->tieneMinimosLegales())->toBeFalse();

    // 1. Acceso público al formulario debe responder 503
    $responseIndex = $this->get(route('libro.publico', ['slug' => $empresaIncompleta->slug]));
    $responseIndex->assertStatus(503);

    // 2. Intento de registro POST al endpoint público debe responder 503
    $responseRegistrar = $this->post(route('libro.registrar', ['slug' => $empresaIncompleta->slug]), [
        'nombres' => 'Juan',
    ]);
    $responseRegistrar->assertStatus(503);
});

test('P2-12: LibroReclamacionesController permite acceso cuando la empresa cumple todos los minimos legales', function () {
    // Empresa con RUC, razón social, dirección y correo de contacto
    $empresaCompleta = Empresa::create([
        'name' => 'Empresa Conforme SAC',
        'slug' => 'empresa-conforme',
        'razon_social' => 'EMPRESA CONFORME S.A.C.',
        'ruc' => '20556677889',
        'direccion' => 'Av. Conforme 456, San Isidro',
        'email' => 'reclamos@empresa-conforme.pe',
        'telefono' => '01-4455667',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
        'plan' => PlanEmpresa::Basico,
    ]);

    expect($empresaCompleta->tieneMinimosLegales())->toBeTrue();

    // El formulario público carga normalmente (200 OK)
    $response = $this->get(route('libro.publico', ['slug' => $empresaCompleta->slug]));
    $response->assertOk();
    $response->assertSee('EMPRESA CONFORME S.A.C.');
});

test('P3-01: StoreEmpresaRequest y UpdateEmpresaRequest rechazan slugs que coinciden con palabras reservadas', function () {
    $this->actingAs($this->superadmin);

    // 1. StoreEmpresaRequest: intento de crear empresa con slug reservado 'admin'
    $responseStoreAdmin = $this->post(route('admin.empresas.store'), [
        'name' => 'Comercio Admin',
        'razon_social' => 'COMERCIO ADMIN S.A.C.',
        'ruc' => '20778899001',
        'slug' => 'admin',
        'direccion' => 'Av. Test 123',
        'email' => 'admin@test.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);
    $responseStoreAdmin->assertSessionHasErrors(['slug']);

    // 2. StoreEmpresaRequest: intento con slug reservado 'login'
    $responseStoreLogin = $this->post(route('admin.empresas.store'), [
        'name' => 'Portal Login',
        'razon_social' => 'PORTAL LOGIN S.A.C.',
        'ruc' => '20778899002',
        'slug' => 'login',
        'direccion' => 'Av. Test 123',
        'email' => 'login@test.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);
    $responseStoreLogin->assertSessionHasErrors(['slug']);

    // 3. StoreEmpresaRequest: con slug válido no reservado se crea con éxito
    $responseValido = $this->post(route('admin.empresas.store'), [
        'name' => 'Tienda Exclusiva',
        'razon_social' => 'TIENDA EXCLUSIVA S.A.C.',
        'ruc' => '20778899003',
        'slug' => 'tienda-exclusiva',
        'direccion' => 'Av. Test 123',
        'email' => 'contacto@tienda.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);
    $responseValido->assertRedirect(route('admin.empresas.index'));
    $this->assertDatabaseHas('tenants', ['slug' => 'tienda-exclusiva']);

    $empresaCreada = Empresa::where('slug', 'tienda-exclusiva')->first();

    // 4. UpdateEmpresaRequest: intento de actualizar a slug reservado 'api'
    $responseUpdateApi = $this->put(route('admin.empresas.update', $empresaCreada), [
        'name' => 'Tienda Exclusiva',
        'razon_social' => 'TIENDA EXCLUSIVA S.A.C.',
        'ruc' => '20778899003',
        'slug' => 'api',
        'direccion' => 'Av. Test 123',
        'email' => 'contacto@tienda.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);
    $responseUpdateApi->assertSessionHasErrors(['slug']);

    // 5. Autogeneración de slug: Si el nombre es 'Admin', el fallback no genera 'admin' sino 'admin-1'
    $responseAutogenerado = $this->post(route('admin.empresas.store'), [
        'name' => 'Admin',
        'razon_social' => 'ADMIN EMPRESA S.A.C.',
        'ruc' => '20778899004',
        'slug' => '',
        'direccion' => 'Av. Test 123',
        'email' => 'auto@test.pe',
        'plan' => PlanEmpresa::Basico->value,
        'estado' => EstadoEmpresa::Activa->value,
    ]);
    $responseAutogenerado->assertRedirect(route('admin.empresas.index'));
    $this->assertDatabaseHas('tenants', ['ruc' => '20778899004', 'slug' => 'admin-1']);
});
