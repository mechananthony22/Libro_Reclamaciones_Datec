<?php

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoReclamacion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionAdjunto;
use App\Models\User;
use App\Services\SalidaClienteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->empresa = Empresa::create([
        'name' => 'Comercial Lima SAC',
        'razon_social' => 'COMERCIAL LIMA S.A.C.',
        'ruc' => '20556677889',
        'direccion' => 'Av. Los Próceres 100',
        'slug' => 'comercial-lima',
        'email' => 'admin@comerciallima.pe',
        'representante_email' => 'admin@comerciallima.pe',
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

    $this->usuarioAdmin = User::create([
        'name' => 'Admin Empresa',
        'email' => 'admin@comerciallima.pe',
        'password' => Hash::make('password123'),
    ]);
    $this->usuarioAdmin->empresas()->attach($this->empresa->id, ['rol' => RolEmpresa::AdminEmpresa->value, 'activo' => true]);
});

test('P3-06: SalidaClienteService genera y almacena paquetes de custodia usando Storage disk local', function () {
    $salidaService = app(SalidaClienteService::class);
    $paquete = $salidaService->generarPaqueteCustodia($this->empresa, $this->superadmin);

    $disco = Storage::disk('local');
    $directorioCustodia = "custodia/{$this->empresa->id}";

    // Debe existir en el disco local configurado por Laravel
    expect($disco->exists($directorioCustodia))->toBeTrue();
    expect($disco->exists("{$directorioCustodia}/{$paquete['filename']}"))->toBeTrue();
    expect($paquete['path'])->toBe($disco->path("{$directorioCustodia}/{$paquete['filename']}"));

    // Generar un segundo paquete debe limpiar el paquete anterior huérfano
    $paquete2 = $salidaService->generarPaqueteCustodia($this->empresa, $this->superadmin);
    expect($disco->exists("{$directorioCustodia}/{$paquete2['filename']}"))->toBeTrue();

    // Solo debe quedar 1 archivo ZIP en el directorio de la empresa
    $archivos = $disco->files($directorioCustodia);
    $zips = array_filter($archivos, fn ($f) => str_ends_with($f, '.zip'));
    expect(count($zips))->toBe(1);

    // SalidaClienteController@descargarPaquete descarga el archivo desde Storage
    $this->actingAs($this->superadmin);
    $response = $this->get(route('admin.empresas.custodia.descargar', $this->empresa->id));
    $response->assertOk();
    $response->assertHeader('content-disposition');
});

test('P3-07: AuditoriaController exportar procesa la bitacora mediante streaming cursor sin get()', function () {
    // Crear múltiples registros de auditoría
    for ($i = 1; $i <= 5; $i++) {
        AuditoriaLog::create([
            'empresa_id' => $this->empresa->id,
            'modulo' => 'seguridad',
            'accion' => "accion_prueba_{$i}",
            'actor_tipo' => 'usuario',
            'actor_id' => $this->usuarioAdmin->id,
            'actor_nombre' => $this->usuarioAdmin->name,
            'actor_email' => $this->usuarioAdmin->email,
            'descripcion' => "Descripción del evento de auditoría número {$i}",
            'ip' => '127.0.0.1',
        ]);
    }

    $this->empresa->makeCurrent();
    $this->actingAs($this->usuarioAdmin)
        ->withSession(['current_tenant_id' => $this->empresa->id]);

    $response = $this->get(route('empresa.auditoria.exportar'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    // Consumir el contenido transmitido
    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    // Validar BOM UTF-8 y encabezados
    expect($csvContent)->toStartWith("\xEF\xBB\xBF");
    expect($csvContent)->toContain('Fecha y Hora (Registro)');
    expect($csvContent)->toContain('Módulo');
    expect($csvContent)->toContain('Acción');

    // Validar contenido de las filas
    for ($i = 1; $i <= 5; $i++) {
        expect($csvContent)->toContain("accion_prueba_{$i}");
        expect($csvContent)->toContain("Descripción del evento de auditoría número {$i}");
    }

    // Verificar que se registró la acción de auditoría con el total exacto
    $this->assertDatabaseHas('auditoria_logs', [
        'empresa_id' => $this->empresa->id,
        'accion' => 'auditoria_exportada',
    ]);
});

test('el paquete de custodia incluye los archivos adjuntos del consumidor usando storage_path', function () {
    $reclamacion = Reclamacion::create([
        'empresa_id' => $this->empresa->id,
        'numero_hoja' => 'CUSTODIA-001',
        'tipo' => TipoReclamacion::Reclamo,
        'tipo_bien' => TipoBien::Producto,
        'descripcion_bien' => 'Laptop Gamer',
        'detalle_problema' => 'Pantalla defectuosa',
        'pedido_consumidor' => 'Cambio de equipo',
        'aviso_privacidad_leido' => true,
        'aviso_privacidad_version' => '1.0',
        'aviso_privacidad_aceptado_en' => now(),
        'datos_consumidor' => ['nombres' => 'Juan', 'apellidos' => 'Perez', 'email' => 'juan@ejemplo.com'],
        'datos_legales_snapshot' => ['razon_social' => 'COMERCIAL LIMA S.A.C.', 'ruc' => '20556677889'],
        'estado' => EstadoReclamacion::Pendiente,
        'fecha_hora' => now(),
    ]);

    // Crear un archivo físico real en el disco local
    $rutaArchivoDisco = "evidencias/{$this->empresa->id}/{$reclamacion->id}/boleta_compra.pdf";
    $contenidoArchivo = '%PDF-1.4 Evidencia Boleta de Compra Custodia Test';
    Storage::disk('local')->put($rutaArchivoDisco, $contenidoArchivo);

    ReclamacionAdjunto::create([
        'empresa_id' => $this->empresa->id,
        'reclamacion_id' => $reclamacion->id,
        'nombre_original' => 'Boleta_Original_123.pdf',
        'storage_path' => $rutaArchivoDisco,
        'mime_type' => 'application/pdf',
        'tamano_bytes' => strlen($contenidoArchivo),
        'hash_sha256' => hash('sha256', $contenidoArchivo),
        'origen' => 'consumidor',
    ]);

    $salidaService = app(SalidaClienteService::class);
    $paquete = $salidaService->generarPaqueteCustodia($this->empresa, $this->superadmin);

    $zip = new ZipArchive;
    $opened = $zip->open($paquete['path']);
    expect($opened)->toBeTrue();

    // Debe contener el PDF de la constancia
    expect($zip->getFromName("constancias/{$reclamacion->numero_hoja}.pdf"))->not->toBeFalse();

    // DEBE contener el archivo adjunto bajo adjuntos/{numero_hoja}/{nombre_original}
    $contenidoAdjuntoEnZip = $zip->getFromName("adjuntos/{$reclamacion->numero_hoja}/Boleta_Original_123.pdf");
    expect($contenidoAdjuntoEnZip)->toBe($contenidoArchivo);

    $zip->close();
});
