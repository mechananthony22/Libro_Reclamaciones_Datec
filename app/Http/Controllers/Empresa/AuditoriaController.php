<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador del Historial de Auditoría a nivel de la empresa activa.
 * Provee trazabilidad estricta y aislada por tenant de todas las acciones corporativas,
 * cambios de configuración, gestión de accesos, respuestas y exportaciones de registros.
 */
class AuditoriaController extends Controller
{
    /**
     * Muestra la bitácora de auditoría de la empresa con filtros.
     */
    public function index(Request $request): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $query = $this->construirQuery($request, $empresa);
        $logs = $query->paginate(20)->withQueryString();

        $modulos = [
            ['value' => 'seguridad', 'label' => 'Seguridad y Accesos'],
            ['value' => 'configuracion', 'label' => 'Configuración de Tenant'],
            ['value' => 'usuarios', 'label' => 'Usuarios y Roles'],
            ['value' => 'establecimientos', 'label' => 'Sedes y Sucursales'],
            ['value' => 'expedientes', 'label' => 'Expedientes de Reclamos'],
        ];

        return Inertia::render('Empresa/Auditoria/Index', [
            'logs' => $logs,
            'modulos' => $modulos,
            'filtros' => [
                'modulo' => $request->input('modulo', ''),
                'buscar' => $request->input('buscar', ''),
                'fecha_desde' => $request->input('fecha_desde', ''),
                'fecha_hasta' => $request->input('fecha_hasta', ''),
            ],
        ]);
    }

    /**
     * Exporta la bitácora de auditoría de la empresa a formato CSV con BOM UTF-8 y snapshot pericial.
     */
    public function exportar(Request $request): StreamedResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $query = $this->construirQuery($request, $empresa);
        $logs = $query->get();

        // Registrar auditoría del reporte de trazabilidad descargado
        app(AuditoriaService::class)->registrar(
            accion: 'auditoria_exportada',
            modulo: 'seguridad',
            descripcion: "Exportación de la bitácora de auditoría en formato CSV (Total: {$logs->count()} registros)",
            opciones: [
                'empresa_id' => $empresa->id,
                'user' => $request->user(),
                'datos_despues' => [
                    'total_registros' => $logs->count(),
                    'filtros' => $request->only(['modulo', 'buscar', 'fecha_desde', 'fecha_hasta']),
                ],
            ]
        );

        $timestamp = now('America/Lima')->format('Ymd_His');
        $nombreArchivo = "auditoria_{$empresa->ruc}_{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para apertura correcta en Microsoft Excel y software de auditoría
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Fecha y Hora (Registro)',
                'Módulo',
                'Acción',
                'Tipo de Actor',
                'Nombre del Actor',
                'Email del Actor',
                'Descripción del Evento',
                'Dirección IP',
                'Datos Previos (Snapshot)',
                'Datos Posteriores (Snapshot)',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->setTimezone('America/Lima')->format('Y-m-d H:i:s'),
                    strtoupper($log->modulo),
                    $log->accion,
                    $log->actor_tipo,
                    $log->actor_nombre,
                    $log->actor_email,
                    $log->descripcion,
                    $log->ip,
                    $log->datos_antes ? json_encode($log->datos_antes, JSON_UNESCAPED_UNICODE) : '',
                    $log->datos_despues ? json_encode($log->datos_despues, JSON_UNESCAPED_UNICODE) : '',
                ]);
            }

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    /**
     * Construye la consulta filtrada asegurando aislamiento estricto por tenant.
     *
     * @return Builder<AuditoriaLog>
     */
    protected function construirQuery(Request $request, Empresa $empresa): Builder
    {
        $query = AuditoriaLog::where('empresa_id', $empresa->id)
            ->where('actor_tipo', '!=', 'superadmin')
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($modulo = $request->input('modulo')) {
            $query->where('modulo', $modulo);
        }

        if ($buscar = trim((string) $request->input('buscar', ''))) {
            $esPgsql = config('database.default') === 'pgsql';
            $like = $esPgsql ? 'ilike' : 'like';

            $query->where(function ($q) use ($buscar, $like) {
                $q->where('descripcion', $like, "%{$buscar}%")
                    ->orWhere('actor_nombre', $like, "%{$buscar}%")
                    ->orWhere('actor_email', $like, "%{$buscar}%")
                    ->orWhere('ip', $like, "%{$buscar}%");
            });
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        return $query;
    }
}
