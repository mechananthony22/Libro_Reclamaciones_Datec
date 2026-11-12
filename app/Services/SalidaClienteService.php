<?php

namespace App\Services;

use App\Enums\EstadoEmpresa;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Servicio encargado de procesar la salida, baja formal y retención legal
 * de clientes (tenants), gestionando la revocación inmediata de accesos,
 * custodia probatoria por el plazo legal mínimo (24 meses) y generación
 * del paquete íntegro de exportación con hash SHA-256 de forma atómica y protegida.
 */
class SalidaClienteService
{
    public function __construct(
        protected AuditoriaService $auditoriaService,
        protected PdfConstanciaService $pdfService
    ) {}

    /**
     * Procesa la baja operativa y programa el plazo de retención legal para una empresa.
     * Operación estrictamente atómica: primero valida requisitos y genera el paquete íntegro
     * en disco; únicamente si el empaquetado y sellado criptográfico tienen éxito, procede
     * con la transacción de base de datos para desactivar la empresa y revocar usuarios.
     *
     * @return array{empresa: Empresa, paquete: array{path: string, filename: string, hash: string, size: int}}
     */
    public function procesarBaja(
        Empresa $empresa,
        string $motivo,
        ?User $actor = null,
        int $mesesRetencion = 24
    ): array {
        // 1. Verificación previa de requisitos de plataforma
        $this->validarRequisitosPlataforma();

        $fechaBaja = now();
        $retencionHasta = now()->addMonths($mesesRetencion)->toDateString();

        // 2. FASE PREVIA: Construir y sellar el paquete de custodia en disco antes de alterar la BD
        // Si la generación de PDFs, memoria, espacio en disco o ZIP falla, la empresa y usuarios quedan 100% intactos
        $paquete = $this->generarPaqueteCustodia($empresa, $actor);

        // 3. FASE ATÓMICA DE BASE DE DATOS: Aplicar la baja en una sola transacción
        try {
            DB::transaction(function () use ($empresa, $motivo, $actor, $fechaBaja, $retencionHasta, $mesesRetencion, $paquete) {
                // A. Actualizar estado del tenant a Baja Programada y registrar sellado
                $empresa->update([
                    'estado' => EstadoEmpresa::BajaProgramada,
                    'activo' => false,
                    'fecha_baja' => $fechaBaja,
                    'motivo_baja' => $motivo,
                    'retencion_hasta' => $retencionHasta,
                    'salida_autorizada_por_id' => $actor?->id,
                    'fecha_cambio_estado' => $fechaBaja,
                    'motivo_cambio_estado' => 'Baja formal y custodia de pruebas: '.$motivo,
                    'paquete_salida_generado_en' => $fechaBaja,
                    'paquete_salida_hash' => $paquete['hash'],
                ]);

                // B. Revocar inmediatamente el acceso de todos los usuarios vinculados a este tenant
                DB::table('empresa_usuario')
                    ->where('empresa_id', $empresa->id)
                    ->update([
                        'activo' => false,
                        'updated_at' => now(),
                    ]);

                // C. Asentar en la bitácora de auditoría
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
            });
        } catch (\Throwable $e) {
            // Rollback físico: si la base de datos falla, se elimina el ZIP generado para evitar inconsistencias
            if (file_exists($paquete['path'])) {
                @unlink($paquete['path']);
            }
            Log::error("Fallo durante la transacción de baja para la empresa {$empresa->ruc}: ".$e->getMessage());
            throw $e;
        }

        return [
            'empresa' => $empresa->fresh(),
            'paquete' => $paquete,
        ];
    }

    /**
     * Construye y empaqueta los expedientes de reclamación, bitácora de auditoría,
     * constancias PDF y acta de custodia legal en un archivo ZIP con hash SHA-256.
     * Utiliza procesamiento en streaming y recolección de basura periódica para proteger la memoria RAM.
     *
     * @return array{path: string, filename: string, hash: string, size: int}
     */
    public function generarPaqueteCustodia(Empresa $empresa, ?User $actor = null): array
    {
        $this->validarRequisitosPlataforma();

        $directorioDestino = storage_path("app/custodia/{$empresa->id}");
        if (! is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        // Limpieza de paquetes huérfanos anteriores de la misma empresa para proteger el espacio en disco
        $archivosPrevios = glob("{$directorioDestino}/custodia_*.zip");
        if ($archivosPrevios) {
            foreach ($archivosPrevios as $archivoViejo) {
                if (file_exists($archivoViejo)) {
                    @unlink($archivoViejo);
                }
            }
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "custodia_{$empresa->ruc}_{$timestamp}.zip";
        $zipPath = "{$directorioDestino}/{$filename}";

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No fue posible crear el archivo ZIP de custodia en: {$zipPath}");
        }

        // A. Exportar CSV de expedientes de reclamación (Optimizado en memoria)
        $csvExpedientes = $this->generarCsvExpedientes($empresa);
        $zip->addFromString('expedientes_reclamaciones.csv', $csvExpedientes);
        unset($csvExpedientes);

        // B. Exportar CSV de bitácora de auditoría (Optimizado en memoria)
        $csvAuditoria = $this->generarCsvAuditoria($empresa);
        $zip->addFromString('bitacora_auditoria.csv', $csvAuditoria);
        unset($csvAuditoria);

        // C. Generar Acta de Entrega y Custodia Legal
        $actaTexto = $this->generarActaCustodiaTexto($empresa, $actor);
        $zip->addFromString('ACTA_CUSTODIA_LEGAL.txt', $actaTexto);
        unset($actaTexto);

        // D. Generar y adjuntar constancias PDF procesadas en bloques para no saturar memoria RAM
        $queryReclamaciones = Reclamacion::withoutGlobalScopes()
            ->with([
                'establecimiento' => fn ($q) => $q->withoutGlobalScopes(),
                'respuestas' => fn ($q) => $q->withoutGlobalScopes(),
                'adjuntos' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->where('empresa_id', $empresa->id)
            ->orderBy('id', 'asc');

        $totalEsperado = $queryReclamaciones->count();
        $pdfCount = 0;

        foreach ($queryReclamaciones->lazyById(50) as $rec) {
            try {
                $pdf = $this->pdfService->generarPdf($rec);
                $zip->addFromString("constancias/{$rec->numero_hoja}.pdf", $pdf->output());
                unset($pdf); // Liberar de inmediato el árbol DOM y memoria del PDF
                $pdfCount++;
            } catch (\Throwable $e) {
                $zip->close();
                if (file_exists($zipPath)) {
                    @unlink($zipPath);
                }
                Log::error("Fallo al generar PDF para custodia ({$rec->numero_hoja}): ".$e->getMessage());
                throw new \RuntimeException("No fue posible generar la constancia PDF para el expediente {$rec->numero_hoja}: ".$e->getMessage(), 0, $e);
            }

            // Adjuntar archivos de soporte cargados si existen físicamente en disco
            foreach ($rec->adjuntos as $adjunto) {
                if ($adjunto->ruta_archivo && Storage::disk('local')->exists($adjunto->ruta_archivo)) {
                    $nombreAdjunto = $adjunto->nombre_original ?: basename($adjunto->ruta_archivo);
                    $zip->addFromString("adjuntos/{$rec->numero_hoja}/{$nombreAdjunto}", Storage::disk('local')->get($adjunto->ruta_archivo));
                }
            }

            // Recolección periódica de basura para no acumular memoria en lotes grandes
            if ($pdfCount % 25 === 0) {
                gc_collect_cycles();
            }
        }

        // Verificación estricta de integridad probatoria: todas las constancias deben estar presentes
        if ($totalEsperado !== $pdfCount) {
            $zip->close();
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            throw new \RuntimeException("Discrepancia en paquete de custodia: se registraron {$totalEsperado} expedientes pero solo se generaron {$pdfCount} constancias.");
        }

        $zip->close();

        // Calcular hash SHA-256 del paquete completo para garantizar su inmutabilidad
        $hashSha256 = hash_file('sha256', $zipPath);
        $fileSize = filesize($zipPath);

        // Si la empresa ya está en baja o es regeneración manual, actualizar metadatos
        if ($empresa->exists && $empresa->estado === EstadoEmpresa::BajaProgramada) {
            $empresa->update([
                'paquete_salida_generado_en' => now(),
                'paquete_salida_hash' => $hashSha256,
            ]);
        }

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'hash' => $hashSha256,
            'size' => $fileSize,
        ];
    }

    /**
     * Valida que el entorno del servidor cuente con la extensión ZIP y recursos básicos de disco.
     */
    protected function validarRequisitosPlataforma(): void
    {
        if (! extension_loaded('zip')) {
            throw new \RuntimeException('La extensión PHP ext-zip requerida para empaquetado de custodia legal no se encuentra habilitada.');
        }

        // Validación de margen de espacio libre en disco (mínimo 20MB libres de seguridad)
        $espacioLibre = @disk_free_space(storage_path('app'));
        if ($espacioLibre !== false && $espacioLibre < 20 * 1024 * 1024) {
            throw new \RuntimeException('Espacio en disco insuficiente en el servidor para generar el paquete de custodia probatoria.');
        }
    }

    /**
     * Genera el CSV de todos los expedientes de la empresa con codificación UTF-8 con BOM y campos reales del modelo.
     * Utiliza memoria temporal paginada y lazy loading para alta eficiencia.
     */
    protected function generarCsvExpedientes(Empresa $empresa): string
    {
        // php://temp almacena en RAM hasta 2MB; si excede, pagina a disco temporal sin saturar memoria
        $handle = fopen('php://temp/maxmemory:2097152', 'r+');
        // UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ID',
            'Número Hoja',
            'Tipo',
            'Estado',
            'Fecha Presentación',
            'Fecha Límite Legal (Vencimiento)',
            'Consumidor Nombre',
            'Tipo Documento',
            'Número Documento',
            'Email',
            'Teléfono',
            'Dirección',
            'Establecimiento',
            'Tipo Bien',
            'Descripción del Bien',
            'Monto Reclamado',
            'Descripción del Hecho',
            'Pedido del Consumidor',
            'Decisión de Respuesta',
            'Propuesta de Solución',
            'Fundamento Legal Respuesta',
            'Fecha Respuesta',
            'Respuesta Notificada En',
        ]);

        $queryReclamaciones = Reclamacion::withoutGlobalScopes()
            ->with([
                'establecimiento' => fn ($q) => $q->withoutGlobalScopes(),
                'respuestas' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->where('empresa_id', $empresa->id)
            ->orderBy('id', 'asc');

        foreach ($queryReclamaciones->lazyById(100) as $rec) {
            $consumidor = $rec->datos_consumidor ?? [];
            $nombres = trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? ''));
            $respuesta = $rec->respuestas->first();

            fputcsv($handle, [
                $rec->id,
                $rec->numero_hoja,
                $rec->tipo?->value ?? (string) $rec->tipo,
                $rec->estado?->value ?? (string) $rec->estado,
                $rec->fecha_hora?->format('Y-m-d H:i:s') ?? $rec->created_at?->format('Y-m-d H:i:s'),
                $rec->vence_en?->format('Y-m-d') ?? '',
                $nombres,
                $consumidor['tipo_documento'] ?? '',
                $consumidor['numero_documento'] ?? '',
                $consumidor['email'] ?? '',
                $consumidor['telefono'] ?? '',
                $consumidor['direccion'] ?? '',
                $rec->establecimiento?->nombre ?? 'Principal',
                $rec->tipo_bien?->value ?? (string) $rec->tipo_bien,
                $rec->descripcion_bien ?? '',
                $rec->monto_reclamado ?? '',
                $rec->detalle_problema ?? '',
                $rec->pedido_consumidor ?? '',
                $respuesta?->decision?->value ?? (string) ($respuesta?->decision ?? ''),
                $respuesta?->propuesta_solucion ?? '',
                $respuesta?->fundamento_legal ?? '',
                $respuesta?->fecha_respuesta?->format('Y-m-d H:i:s') ?? '',
                $respuesta?->notificado_en?->format('Y-m-d H:i:s') ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Genera el CSV de la bitácora de auditoría asociada al tenant con paginación de memoria.
     */
    protected function generarCsvAuditoria(Empresa $empresa): string
    {
        $handle = fopen('php://temp/maxmemory:2097152', 'r+');
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

        $queryLogs = AuditoriaLog::where('empresa_id', $empresa->id)
            ->orderBy('id', 'asc');

        foreach ($queryLogs->lazyById(200) as $log) {
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
        $totalExpedientes = Reclamacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();
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

RESUMEN DE EXPEDIENTES Y EVIDENCIAS CUSTODIADAS:
- Total de reclamaciones y quejas registradas: {$totalExpedientes}
- Total de constancias probatorias PDF generadas: {$totalExpedientes}
- Total de eventos de auditoría y trazabilidad: {$totalLogs}

DECLARACIÓN DE CUMPLIMIENTO LEGAL:
En concordancia con el Código de Protección y Defensa del Consumidor (Ley N° 29571)
y directivas de INDECOPI, la presente custodia preserva de manera íntegra, fidedigna
e inalterable todas las hojas de reclamación, expedientes, resoluciones y constancias
interpuestas durante el periodo operativo del servicio en formato estándar e inmutable.

Se revocan todos los accesos administrativos de los usuarios de la empresa en la
plataforma DatecLibro. Los expedientes quedan asegurados en frío hasta el vencimiento
del plazo de retención fijado.

================================================================================
TEXT;
    }
}
