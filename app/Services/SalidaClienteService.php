<?php

namespace App\Services;

use App\Enums\EstadoEmpresa;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Servicio encargado de procesar la salida, baja formal y retención legal
 * de clientes (tenants), gestionando la revocación inmediata de accesos,
 * custodia probatoria por el plazo legal mínimo (24 meses) y generación
 * del paquete íntegro de exportación con hash SHA-256.
 */
class SalidaClienteService
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Procesa la baja operativa y programa el plazo de retención legal para una empresa.
     * Revoca accesos de sus usuarios y emite el paquete digital de custodia probatoria.
     */
    public function procesarBaja(
        Empresa $empresa,
        string $motivo,
        ?User $actor = null,
        int $mesesRetencion = 24
    ): array {
        $fechaBaja = now();
        $retencionHasta = now()->addMonths($mesesRetencion)->toDateString();

        // 1. Actualizar estado del tenant y registrar la baja formal
        $empresa->update([
            'estado' => EstadoEmpresa::BajaProgramada,
            'activo' => false,
            'fecha_baja' => $fechaBaja,
            'motivo_baja' => $motivo,
            'retencion_hasta' => $retencionHasta,
            'salida_autorizada_por_id' => $actor?->id,
            'fecha_cambio_estado' => $fechaBaja,
            'motivo_cambio_estado' => 'Baja formal y custodia de pruebas: '.$motivo,
        ]);

        // 2. Revocar inmediatamente el acceso de todos los usuarios vinculados a este tenant
        DB::table('empresa_usuario')
            ->where('empresa_id', $empresa->id)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);

        // 3. Generar el paquete de custodia legal con hash criptográfico
        $paquete = $this->generarPaqueteCustodia($empresa, $actor);

        // 4. Asentar en la bitácora de auditoría
        $this->auditoriaService->registrar(
            accion: 'baja_empresa',
            modulo: 'empresas',
            descripcion: "Baja formal ejecutada para {$empresa->razon_social}. Retención legal hasta {$retencionHasta} (plazo: {$mesesRetencion} meses). Accesos de usuarios revocados.",
            opciones: [
                'empresa_id' => $empresa->id,
                'user' => $actor,
                'datos_despues' => [
                    'motivo' => $motivo,
                    'fecha_baja' => $fechaBaja->toIso8601String(),
                    'retencion_hasta' => $retencionHasta,
                    'paquete_hash' => $paquete['hash'],
                    'paquete_archivo' => $paquete['filename'],
                ],
            ]
        );

        return [
            'empresa' => $empresa->fresh(),
            'paquete' => $paquete,
        ];
    }

    /**
     * Construye y empaqueta los expedientes de reclamación, bitácora de auditoría,
     * constancias PDF y acta de custodia legal en un archivo ZIP con hash SHA-256.
     *
     * @return array{path: string, filename: string, hash: string, size: int}
     */
    public function generarPaqueteCustodia(Empresa $empresa, ?User $actor = null): array
    {
        $directorioDestino = storage_path("app/custodia/{$empresa->id}");
        if (! is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "custodia_{$empresa->ruc}_{$timestamp}.zip";
        $zipPath = "{$directorioDestino}/{$filename}";

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No fue posible crear el archivo ZIP de custodia en: {$zipPath}");
        }

        // A. Exportar CSV de expedientes de reclamación
        $csvExpedientes = $this->generarCsvExpedientes($empresa);
        $zip->addFromString('expedientes_reclamaciones.csv', $csvExpedientes);

        // B. Exportar CSV de bitácora de auditoría
        $csvAuditoria = $this->generarCsvAuditoria($empresa);
        $zip->addFromString('bitacora_auditoria.csv', $csvAuditoria);

        // C. Generar Acta de Entrega y Custodia Legal
        $actaTexto = $this->generarActaCustodiaTexto($empresa, $actor);
        $zip->addFromString('ACTA_CUSTODIA_LEGAL.txt', $actaTexto);

        // D. Adjuntar constancias PDF almacenadas si existen
        $reclamaciones = Reclamacion::where('empresa_id', $empresa->id)->get();
        foreach ($reclamaciones as $rec) {
            if ($rec->constancia_pdf_path && Storage::disk('local')->exists($rec->constancia_pdf_path)) {
                $contenidoPdf = Storage::disk('local')->get($rec->constancia_pdf_path);
                $zip->addFromString("constancias/{$rec->numero_reclamacion}.pdf", $contenidoPdf);
            }
        }

        $zip->close();

        // Calcular hash SHA-256 del paquete completo para garantizar su inmutabilidad
        $hashSha256 = hash_file('sha256', $zipPath);
        $fileSize = filesize($zipPath);

        $empresa->update([
            'paquete_salida_generado_en' => now(),
            'paquete_salida_hash' => $hashSha256,
        ]);

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'hash' => $hashSha256,
            'size' => $fileSize,
        ];
    }

    /**
     * Genera el CSV de todos los expedientes de la empresa con codificación UTF-8 con BOM.
     */
    protected function generarCsvExpedientes(Empresa $empresa): string
    {
        $handle = fopen('php://memory', 'r+');
        // UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ID',
            'Número Reclamación',
            'Tipo',
            'Estado',
            'Fecha Registro',
            'Consumidor Nombre',
            'Tipo Documento',
            'Número Documento',
            'Email',
            'Teléfono',
            'Dirección',
            'Establecimiento',
            'Tipo Bien',
            'Monto Reclamado',
            'Descripción del Hecho',
            'Pedido del Consumidor',
            'Detalle de Respuesta',
            'Fecha Respuesta',
            'Respuesta Notificada En',
        ]);

        $reclamaciones = Reclamacion::with('establecimiento')
            ->where('empresa_id', $empresa->id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($reclamaciones as $rec) {
            fputcsv($handle, [
                $rec->id,
                $rec->numero_reclamacion,
                $rec->tipo?->value ?? (string) $rec->tipo,
                $rec->estado?->value ?? (string) $rec->estado,
                $rec->created_at?->format('Y-m-d H:i:s'),
                $rec->consumidor_nombre.' '.$rec->consumidor_apellido,
                $rec->consumidor_tipo_documento,
                $rec->consumidor_numero_documento,
                $rec->consumidor_email,
                $rec->consumidor_telefono,
                $rec->consumidor_domicilio,
                $rec->establecimiento?->nombre ?? 'Principal',
                $rec->tipo_bien,
                $rec->monto_reclamado,
                $rec->detalle_reclamacion,
                $rec->pedido_consumidor,
                $rec->respuesta_texto,
                $rec->fecha_respuesta?->format('Y-m-d H:i:s'),
                $rec->fecha_notificacion_respuesta?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Genera el CSV de la bitácora de auditoría asociada al tenant.
     */
    protected function generarCsvAuditoria(Empresa $empresa): string
    {
        $handle = fopen('php://memory', 'r+');
        // UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ID',
            'Fecha',
            'Módulo',
            'Acción',
            'Actor Tipo',
            'Actor Nombre',
            'Actor Email',
            'Descripción',
            'Dirección IP',
        ]);

        $logs = AuditoriaLog::where('empresa_id', $empresa->id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->created_at?->format('Y-m-d H:i:s'),
                $log->modulo,
                $log->accion,
                $log->actor_tipo,
                $log->actor_nombre,
                $log->actor_email,
                $log->descripcion,
                $log->ip,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Redacta el documento formal de custodia probatoria para constancia legal.
     */
    protected function generarActaCustodiaTexto(Empresa $empresa, ?User $actor = null): string
    {
        $fechaActual = now()->toDayDateTimeString();
        $retencionHasta = $empresa->retencion_hasta ? $empresa->retencion_hasta->format('Y-m-d') : now()->addMonths(24)->format('Y-m-d');
        $totalExpedientes = Reclamacion::where('empresa_id', $empresa->id)->count();
        $totalLogs = AuditoriaLog::where('empresa_id', $empresa->id)->count();
        $autorizadoPor = $actor ? "{$actor->name} ({$actor->email})" : 'Administración Central Datec';

        return <<<TEXT
================================================================================
           ACTA FORMAL DE DESACTIVACIÓN, SALIDA Y CUSTODIA PROBATORIA
================================================================================
EMPRESA: {$empresa->razon_social}
RUC: {$empresa->ruc}
DIRECCIÓN REGISTRADA: {$empresa->direccion}
EMAIL DE CONTACTO: {$empresa->email}

FECHA DE GENERACIÓN: {$fechaActual}
AUTORIZADO POR: {$autorizadoPor}
ESTADO DE PLATAFORMA: BAJA PROGRAMADA / CUSTODIA DE PRUEBAS
PLAZO DE RETENCIÓN LEGAL HASTA: {$retencionHasta} (24 meses calendario)

RESUMEN DE EXPEDIENTES CUSTODIADOS:
- Total de reclamaciones y quejas registradas: {$totalExpedientes}
- Total de eventos de auditoría y trazabilidad: {$totalLogs}

DECLARACIÓN DE CUMPLIMIENTO LEGAL:
En concordancia con el Código de Protección y Defensa del Consumidor (Ley N° 29571)
y directivas de INDECOPI, la presente custodia preserva de manera íntegra, fidedigna
e inalterable todas las reclamaciones interpuestas por los consumidores durante el
periodo operativo del servicio.

Se revocan todos los accesos administrativos de los usuarios de la empresa en la
plataforma DatecLibro. Los expedientes quedan asegurados en frío hasta el vencimiento
del plazo de retención fijado.

================================================================================
TEXT;
    }
}
