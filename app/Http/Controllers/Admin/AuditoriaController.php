<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de auditoría global para el Superadministrador Datec.
 * Permite visualizar el historial inmutable de todas las acciones corporativas,
 * accesos privilegiados, modificaciones de configuración y operaciones SaaS.
 */
class AuditoriaController extends Controller
{
    /**
     * Muestra la bitácora centralizada de eventos de auditoría con filtros.
     */
    public function index(Request $request): Response
    {
        $query = AuditoriaLog::query()
            ->with(['empresa:id,name,slug,ruc', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filtro por empresa
        if ($empresaId = $request->input('empresa_id')) {
            $query->where('empresa_id', $empresaId);
        }

        // Filtro por módulo (seguridad, empresas, configuracion, usuarios, expedientes)
        if ($modulo = $request->input('modulo')) {
            $query->where('modulo', $modulo);
        }

        // Filtro por acción específica
        if ($accion = $request->input('accion')) {
            $query->where('accion', $accion);
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

        // Filtro por fechas
        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $logs = $query->paginate(20)->withQueryString();

        $empresas = Empresa::orderBy('name')->get(['id', 'name', 'slug']);

        $modulos = [
            ['value' => 'seguridad', 'label' => 'Seguridad y Accesos'],
            ['value' => 'empresas', 'label' => 'Empresas SaaS'],
            ['value' => 'configuracion', 'label' => 'Configuración de Tenant'],
            ['value' => 'usuarios', 'label' => 'Usuarios y Roles'],
            ['value' => 'establecimientos', 'label' => 'Sedes y Sucursales'],
            ['value' => 'expedientes', 'label' => 'Expedientes de Reclamos'],
        ];

        return Inertia::render('Admin/Auditoria/Index', [
            'logs' => $logs,
            'empresas' => $empresas,
            'modulos' => $modulos,
            'filtros' => [
                'empresa_id' => $request->input('empresa_id', ''),
                'modulo' => $request->input('modulo', ''),
                'accion' => $request->input('accion', ''),
                'buscar' => $request->input('buscar', ''),
                'fecha_desde' => $request->input('fecha_desde', ''),
                'fecha_hasta' => $request->input('fecha_hasta', ''),
            ],
        ]);
    }
}

