<?php

use App\Enums\EstadoEmpresa;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Modelo temporal para verificar el aislamiento estricto de RF-007
class DummyTenantModel extends Model
{
    use BelongsToEmpresa;

    protected $table = 'test_tenant_items';

    protected $fillable = ['empresa_id', 'titulo'];
}

beforeEach(function () {
    if (! Schema::hasTable('test_tenant_items')) {
        Schema::create('test_tenant_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id');
            $table->string('titulo');
            $table->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('test_tenant_items');
});

test('RF-006: slug válido resuelve la empresa activa y muestra el Libro de Reclamaciones', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Test Alfa',
        'slug' => 'empresa-test-alfa',
        'razon_social' => 'EMPRESA TEST ALFA S.A.C.',
        'ruc' => '20123456781',
        'direccion' => 'Av. Central 123, Lima',
        'email' => 'alfa@test.pe',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $response = $this->get("/l/{$empresa->slug}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Publico/LibroReclamaciones')
        ->where('empresa.slug', 'empresa-test-alfa')
        ->where('empresa.ruc', '20123456781')
    );
});

test('RF-006: slug inexistente retorna 404 seguro y no filtra información interna', function () {
    $response = $this->get('/l/empresa-completamente-inexistente');

    $response->assertStatus(404);
});

test('RF-006: empresa suspendida retorna 403 con mensaje de servicio suspendido', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Suspendida',
        'slug' => 'empresa-suspendida',
        'razon_social' => 'EMPRESA SUSPENDIDA S.A.C.',
        'ruc' => '20123456782',
        'direccion' => 'Av. Callao 456, Lima',
        'estado' => EstadoEmpresa::Suspendida,
        'activo' => false,
    ]);

    $response = $this->get("/l/{$empresa->slug}");

    $response->assertStatus(403);
});

test('RF-007: el scope global impide ver registros pertenecientes a otra empresa (Cross-tenant leak)', function () {
    $empresaA = Empresa::create([
        'name' => 'Empresa A',
        'slug' => 'empresa-a',
        'razon_social' => 'EMPRESA A S.A.C.',
        'ruc' => '20123456783',
        'direccion' => 'Calle 1, Lima',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresaB = Empresa::create([
        'name' => 'Empresa B',
        'slug' => 'empresa-b',
        'razon_social' => 'EMPRESA B S.A.C.',
        'ruc' => '20123456784',
        'direccion' => 'Calle 2, Lima',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    // Crear un registro bajo el contexto de Empresa A
    $empresaA->makeCurrent();
    $itemA = DummyTenantModel::create(['titulo' => 'Expediente confidencial A']);

    // Crear un registro bajo el contexto de Empresa B
    $empresaB->makeCurrent();
    $itemB = DummyTenantModel::create(['titulo' => 'Expediente confidencial B']);

    // Solo debe listar los registros de Empresa B
    $registrosVisibles = DummyTenantModel::all();
    expect($registrosVisibles)->toHaveCount(1)
        ->and($registrosVisibles->first()->titulo)->toBe('Expediente confidencial B');

    // Intentar buscar directamente por ID el registro de Empresa A debe arrojar null / 404
    $intentoCrossTenant = DummyTenantModel::find($itemA->id);
    expect($intentoCrossTenant)->toBeNull();
});

test('RF-007: modificar o forzar el empresa_id de otra empresa arroja 403 Forbidden', function () {
    $empresaA = Empresa::create([
        'name' => 'Empresa A',
        'slug' => 'empresa-a-2',
        'razon_social' => 'EMPRESA A2 S.A.C.',
        'ruc' => '20123456785',
        'direccion' => 'Calle 1, Lima',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresaB = Empresa::create([
        'name' => 'Empresa B',
        'slug' => 'empresa-b-2',
        'razon_social' => 'EMPRESA B2 S.A.C.',
        'ruc' => '20123456786',
        'direccion' => 'Calle 2, Lima',
        'estado' => EstadoEmpresa::Activa,
        'activo' => true,
    ]);

    $empresaA->makeCurrent();
    $itemA = DummyTenantModel::create(['titulo' => 'Registro original A']);

    // Intentar cambiar de contexto a Empresa B y alterar el registro de A
    $empresaB->makeCurrent();

    expect(function () use ($itemA, $empresaB) {
        $itemA->empresa_id = $empresaB->id;
        $itemA->save();
    })->toThrow(HttpException::class);
});
