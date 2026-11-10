<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\PlataformaAlerta;
use App\Models\Reclamacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio encargado de monitorear la disponibilidad operativa, uso de recursos,
 * tareas pendientes en cola, fallos de mensajería y evaluación de umbrales para Datec.
 */
class PlataformaSaludService
{
    /**
     * Umbrales operativos estándar configurados para la plataforma SaaS.
     */
    public const UMBRAL_COLA_PENDIENTES_WARN = 50;

    public const UMBRAL_COLA_PENDIENTES_CRIT = 150;

    public const UMBRAL_COLA_FALLIDOS_WARN = 5;

    public const UMBRAL_COLA_FALLIDOS_CRIT = 20;

    public const UMBRAL_ENVIOS_FALLIDOS_WARN = 5;

    public const UMBRAL_ENVIOS_FALLIDOS_CRIT = 20;

    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Obtiene el diagnóstico completo del estado de recursos del sistema.
     *
     * @return array<string, mixed>
     */
    public function obtenerEstadoRecursos(): array
    {
        $tamanoBdMb = 0;
        try {
            $resultado = DB::select('SELECT pg_size_pretty(pg_database_size(current_database())) as tamano, pg_database_size(current_database()) as bytes');
            $tamanoBdTexto = $resultado[0]->tamano ?? 'N/D';
            $tamanoBdMb = isset($resultado[0]->bytes) ? round($resultado[0]->bytes / (1024 * 1024), 2) : 0;
        } catch (\Throwable $e) {
            $tamanoBdTexto = 'No disponible';
        }

        // Espacio libre en disco
        $espacioLibreGb = 0;
        $directorioStorage = storage_path('app');
        if (function_exists('disk_free_space') && @disk_free_space($directorioStorage) !== false) {
            $espacioLibreGb = round(disk_free_space($directorioStorage) / (1024 * 1024 * 1024), 2);
        }

        return [
            'base_datos' => [
                'driver' => DB::connection()->getDriverName(),
                'tamano_formateado' => $tamanoBdTexto,
                'tamano_mb' => $tamanoBdMb,
                'estado' => 'conectado',
            ],
            'almacenamiento' => [
                'espacio_libre_gb' => $espacioLibreGb,
                'ruta' => $directorioStorage,
            ],
            'conteos' => [
                'empresas_total' => Empresa::count(),
                'reclamaciones_total' => Reclamacion::count(),
                'auditorias_total' => DB::table('auditoria_logs')->count(),
            ],
            'servidor' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'zona_horaria' => config('app.timezone'),
            ],
        ];
    }

    /**
     * Evalúa las métricas operativas contra los umbrales definidos y registra alertas para Datec.
     *
     * @return array{
     *     estado_global: string,
     *     alertas_activas: array,
     *     metricas: array,
     *     umbrales: array,
     *     recursos: array,
     * }
     */
    public function evaluarUmbralesYAlertar(): array
    {
        // 1. Recolección de métricas operativas
        $jobsPendientes = DB::table('jobs')->count();
        $jobsFallidos = DB::table('failed_jobs')->count();
        $enviosFallidos = DB::table('reclamacion_envios')->where('estado', 'fallido')->count();
        $contingenciasActivas = Empresa::where('en_contingencia', true)->count();

        $metricas = [
            'jobs_pendientes' => $jobsPendientes,
            'jobs_fallidos' => $jobsFallidos,
            'envios_fallidos' => $enviosFallidos,
            'contingencias_activas' => $contingenciasActivas,
        ];

        $umbrales = [
            'jobs_pendientes' => ['warn' => self::UMBRAL_COLA_PENDIENTES_WARN, 'crit' => self::UMBRAL_COLA_PENDIENTES_CRIT],
            'jobs_fallidos' => ['warn' => self::UMBRAL_COLA_FALLIDOS_WARN, 'crit' => self::UMBRAL_COLA_FALLIDOS_CRIT],
            'envios_fallidos' => ['warn' => self::UMBRAL_ENVIOS_FALLIDOS_WARN, 'crit' => self::UMBRAL_ENVIOS_FALLIDOS_CRIT],
            'contingencias_activas' => ['warn' => 1, 'crit' => 3],
        ];

        // 2. Evaluación individual de cada condición
        $this->evaluarReglaAlerta(
            tipo: 'cola_pendientes',
            valorActual: $jobsPendientes,
            umbralWarn: self::UMBRAL_COLA_PENDIENTES_WARN,
            umbralCrit: self::UMBRAL_COLA_PENDIENTES_CRIT,
            titulo: 'Saturación en Cola de Tareas Asíncronas',
            descripcionWarn: "Se registran {$jobsPendientes} tareas pendientes en espera. Se recomienda supervisar el worker.",
            descripcionCrit: "Alerta crítica: {$jobsPendientes} tareas acumuladas en la cola de procesamiento."
        );

        $this->evaluarReglaAlerta(
            tipo: 'jobs_fallidos',
            valorActual: $jobsFallidos,
            umbralWarn: self::UMBRAL_COLA_FALLIDOS_WARN,
            umbralCrit: self::UMBRAL_COLA_FALLIDOS_CRIT,
            titulo: 'Trabajos Fallidos en Cola PostgreSQL',
            descripcionWarn: "Se detectan {$jobsFallidos} trabajos fallidos en la tabla failed_jobs pendientes de revisión.",
            descripcionCrit: "Alerta crítica: {$jobsFallidos} trabajos fallidos acumulados en la cola."
        );

        $this->evaluarReglaAlerta(
            tipo: 'envios_fallidos',
            valorActual: $enviosFallidos,
            umbralWarn: self::UMBRAL_ENVIOS_FALLIDOS_WARN,
            umbralCrit: self::UMBRAL_ENVIOS_FALLIDOS_CRIT,
            titulo: 'Fallos en Mensajería y Despacho de Correo',
            descripcionWarn: "Se acumulan {$enviosFallidos} envíos de constancias con fallo de transmisión SMTP.",
            descripcionCrit: "Alerta crítica en canal de mensajería: {$enviosFallidos} fallos registrados."
        );

        $this->evaluarReglaAlerta(
            tipo: 'contingencia_activa',
            valorActual: $contingenciasActivas,
            umbralWarn: 1,
            umbralCrit: 3,
            titulo: 'Empresas Clientes en Modo de Contingencia',
            descripcionWarn: "Existe {$contingenciasActivas} empresa cliente operando bajo plan de contingencia.",
            descripcionCrit: "Múltiples clientes ({$contingenciasActivas}) operando bajo modo de contingencia simultáneo."
        );

        // 3. Obtener alertas vigentes
        $alertasActivas = PlataformaAlerta::where('estado', 'activa')
            ->orderByRaw("CASE WHEN severidad = 'critica' THEN 1 WHEN severidad = 'advertencia' THEN 2 ELSE 3 END")
            ->orderBy('id', 'desc')
            ->get();

        $estadoGlobal = 'optimo';
        if ($alertasActivas->contains(fn ($a) => $a->severidad === 'critica')) {
            $estadoGlobal = 'critico';
        } elseif ($alertasActivas->isNotEmpty()) {
            $estadoGlobal = 'advertencia';
        }

        return [
            'estado_global' => $estadoGlobal,
            'alertas_activas' => $alertasActivas->toArray(),
            'metricas' => $metricas,
            'umbrales' => $umbrales,
            'recursos' => $this->obtenerEstadoRecursos(),
        ];
    }

    /**
     * Evalúa una regla específica, abriendo, escalando o resolviendo la alerta correspondiente.
     */
    protected function evaluarReglaAlerta(
        string $tipo,
        int $valorActual,
        int $umbralWarn,
        int $umbralCrit,
        string $titulo,
        string $descripcionWarn,
        string $descripcionCrit
    ): void {
        $alertaExistente = PlataformaAlerta::where('tipo', $tipo)
            ->where('estado', 'activa')
            ->first();

        if ($valorActual >= $umbralCrit) {
            $severidad = 'critica';
            $descripcion = $descripcionCrit;
            $umbralDefinido = ">= {$umbralCrit}";
        } elseif ($valorActual >= $umbralWarn) {
            $severidad = 'advertencia';
            $descripcion = $descripcionWarn;
            $umbralDefinido = ">= {$umbralWarn}";
        } else {
            // El valor está dentro del rango seguro. Si existía una alerta activa, se resuelve.
            if ($alertaExistente) {
                $alertaExistente->update([
                    'estado' => 'resuelta',
                    'resuelta_en' => now(),
                    'valor_actual' => (string) $valorActual,
                ]);

                $this->auditoriaService->registrar(
                    accion: 'alerta_resuelta',
                    modulo: 'monitoreo',
                    descripcion: "Condición normalizada: {$titulo}. Valor actual ({$valorActual}) dentro del umbral normal.",
                    opciones: ['datos_despues' => ['tipo' => $tipo, 'valor' => $valorActual]]
                );
            }

            return;
        }

        // Si el valor superó el umbral
        if (! $alertaExistente) {
            $nuevaAlerta = PlataformaAlerta::create([
                'tipo' => $tipo,
                'severidad' => $severidad,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'valor_actual' => (string) $valorActual,
                'umbral_definido' => $umbralDefinido,
                'estado' => 'activa',
                'notificado_datec_en' => now(),
            ]);

            // Notificación registrada para Datec
            Log::warning("[ALERTA OPERATIVA DATEC] {$titulo}: {$descripcion}");

            $this->auditoriaService->registrar(
                accion: 'alerta_generada',
                modulo: 'monitoreo',
                descripcion: "Alerta operativa [{$severidad}]: {$titulo} (Valor: {$valorActual}, Umbral: {$umbralDefinido})",
                opciones: ['datos_despues' => ['alerta_id' => $nuevaAlerta->id, 'tipo' => $tipo, 'severidad' => $severidad]]
            );
        } else {
            // Actualizar si cambió la severidad o el valor actual
            $alertaExistente->update([
                'severidad' => $severidad,
                'valor_actual' => (string) $valorActual,
                'descripcion' => $descripcion,
                'umbral_definido' => $umbralDefinido,
            ]);
        }
    }

    /**
     * Resuelve manualmente una alerta operativa.
     */
    public function resolverAlerta(PlataformaAlerta $alerta): void
    {
        $alerta->update([
            'estado' => 'resuelta',
            'resuelta_en' => now(),
        ]);

        $this->auditoriaService->registrar(
            accion: 'alerta_resuelta_manual',
            modulo: 'monitoreo',
            descripcion: "Alerta operativa #{$alerta->id} ({$alerta->titulo}) marcada como resuelta por operador.",
            opciones: ['datos_despues' => ['alerta_id' => $alerta->id]]
        );
    }
}
