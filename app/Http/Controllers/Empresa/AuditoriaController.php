<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador del Historial de Auditoría a nivel de la empresa activa.
 * Provee trazabilidad estricta y transparente de todas las acciones corporativas,
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

        $query = AuditoriaLog::where('empresa_id', $empresa->id)
            ->where('actor_tipo', '!=', 'superadmin')
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filtro por módulo (seguridad, configuracion, usuarios, establecimientos, expedientes)
        if ($modulo = $request->input('modulo')) {
            $query->where('modulo', $modulo);
        }

        // Filtro por término de búsqueda (descripción, actor o IP)
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

        // Filtro por rango de fechas
        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

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
}
