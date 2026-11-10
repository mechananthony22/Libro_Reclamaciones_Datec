<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use App\Models\PlataformaAlerta;
use App\Services\PlataformaSaludService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonitoreoController extends Controller
{
    public function __construct(
        protected PlataformaSaludService $saludService
    ) {}

    public function index(): Response
    {
        $hoy = now()->toDateString();
        $en30Dias = now()->addDays(30)->toDateString();

        // 1. Métricas generales de empresas
        $empresasTotal = Empresa::count();
        $empresasActivas = Empresa::where('estado', EstadoEmpresa::Activa)->count();
        $empresasSuspendidas = Empresa::where('estado', EstadoEmpresa::Suspendida)->count();
        $empresasBorrador = Empresa::where('estado', EstadoEmpresa::Borrador)->count();
        $empresasBaja = Empresa::where('estado', EstadoEmpresa::BajaProgramada)->count();

        // Distribución por plan
        $distribucionPlanes = [];
        foreach (PlanEmpresa::cases() as $plan) {
            $distribucionPlanes[] = [
                'plan' => $plan->value,
                'label' => $plan->label(),
                'cantidad' => Empresa::where('plan', $plan)->count(),
            ];
        }

        // 2. Suscripciones y vencimientos
        $suscripcionesPorVencer = Empresa::query()
            ->whereNotNull('vigencia_hasta')
            ->whereBetween('vigencia_hasta', [$hoy, $en30Dias])
            ->orderBy('vigencia_hasta')
            ->get(['id', 'name', 'slug', 'plan', 'vigencia_hasta', 'estado', 'color_primario', 'logo_path']);

        $suscripcionesVencidas = Empresa::query()
            ->whereNotNull('vigencia_hasta')
            ->where('vigencia_hasta', '<', $hoy)
            ->orderBy('vigencia_hasta', 'desc')
            ->get(['id', 'name', 'slug', 'plan', 'vigencia_hasta', 'estado', 'color_primario', 'logo_path']);

        // 3. Colas y envíos asíncronos (PostgreSQL database driver)
        $jobsPendientes = DB::table('jobs')->count();
        $jobsFallidos = DB::table('failed_jobs')->count();

        // Obtener últimos 10 fallos sanitizados
        $ultimosFallosRaw = DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(10)
            ->get();

        $fallosSanitizados = $ultimosFallosRaw->map(function ($f) {
            // Extraer clase del job sin exponer contenido de payload
            $nombreJob = 'Desconocido';
            $payload = json_decode($f->payload, true);
            if (isset($payload['displayName'])) {
                $nombreJob = class_basename($payload['displayName']);
            } elseif (isset($payload['job'])) {
                $nombreJob = class_basename($payload['job']);
            }

            // Extraer solo la primera línea del error técnico (evitando correos o datos del consumidor)
            $primeraLineaException = strtok($f->exception, "\n");
            if (strlen($primeraLineaException) > 160) {
                $primeraLineaException = substr($primeraLineaException, 0, 157).'...';
            }

            return [
                'id' => $f->id,
                'uuid' => $f->uuid,
                'queue' => $f->queue,
                'job_name' => $nombreJob,
                'error_resumen' => $primeraLineaException,
                'failed_at' => $f->failed_at,
            ];
        });

        // 4. Métricas de invitaciones
        $invitaciones = [
            'total' => InvitacionEmpresa::count(),
            'pendientes' => InvitacionEmpresa::whereNull('accepted_at')->where('expires_at', '>', now())->count(),
            'aceptadas' => InvitacionEmpresa::whereNotNull('accepted_at')->count(),
            'expiradas' => InvitacionEmpresa::whereNull('accepted_at')->where('expires_at', '<=', now())->count(),
        ];

        return Inertia::render('Admin/Monitoreo/Index', [
            'empresas' => [
                'total' => $empresasTotal,
                'activas' => $empresasActivas,
                'suspendidas' => $empresasSuspendidas,
                'borrador' => $empresasBorrador,
                'baja' => $empresasBaja,
                'planes' => $distribucionPlanes,
            ],
            'suscripciones' => [
                'por_vencer' => $suscripcionesPorVencer,
                'vencidas' => $suscripcionesVencidas,
                'conteo_por_vencer' => $suscripcionesPorVencer->count(),
                'conteo_vencidas' => $suscripcionesVencidas->count(),
            ],
            'colas' => [
                'pendientes' => $jobsPendientes,
                'fallidos' => $jobsFallidos,
                'ultimos_fallos' => $fallosSanitizados,
            ],
            'invitaciones' => $invitaciones,
            'salud' => $this->saludService->evaluarUmbralesYAlertar(),
        ]);
    }

    public function verificarSalud(): RedirectResponse
    {
        $diagnostico = $this->saludService->evaluarUmbralesYAlertar();
        $totalAlertas = count($diagnostico['alertas_activas']);

        $mensaje = $totalAlertas > 0
            ? "Verificación completada: se detectaron {$totalAlertas} alertas operativas activas por desvío de umbrales."
            : 'Verificación completada: todos los servicios y tareas operan dentro de los umbrales normales.';

        return back()->with('success', $mensaje);
    }

    public function resolverAlerta(PlataformaAlerta $alerta): RedirectResponse
    {
        $this->saludService->resolverAlerta($alerta);

        return back()->with('success', "Alerta operativa #{$alerta->id} resuelta correctamente.");
    }

    public function reintentarJob(string $id): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('success', "Trabajo #{$id} reprogramado para reintento en la cola.");
    }

    public function reintentarTodos(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Todos los trabajos fallidos han sido reprogramados para reintento.');
    }

    public function eliminarJob(string $id): RedirectResponse
    {
        Artisan::call('queue:forget', ['id' => $id]);

        return back()->with('success', "Registro de error #{$id} descartado del monitoreo.");
    }
}
