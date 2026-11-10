<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContingenciaIncidente;
use App\Models\Empresa;
use App\Services\ContingenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de supervisión centralizada de contingencias y ensayos técnicos para el Superadministrador.
 */
class ContingenciaController extends Controller
{
    public function __construct(
        protected ContingenciaService $contingenciaService
    ) {}

    /**
     * Muestra la vista global de empresas en contingencia e incidentes recientes.
     */
    public function index(): Response
    {
        $totalEmpresas = Empresa::count();
        $enContingenciaCount = Empresa::where('en_contingencia', true)->count();
        $totalIncidentes = ContingenciaIncidente::count();
        $totalEnsayos = ContingenciaIncidente::where('tipo', 'ensayo_recuperacion')->count();

        $empresas = Empresa::select('id', 'name', 'razon_social', 'ruc', 'en_contingencia', 'contingencia_activada_en', 'contingencia_mensaje')
            ->orderBy('en_contingencia', 'desc')
            ->orderBy('razon_social', 'asc')
            ->get();

        $incidentes = ContingenciaIncidente::with(['empresa:id,razon_social,ruc', 'iniciadoPor:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(40)
            ->get()
            ->map(fn (ContingenciaIncidente $inc) => [
                'id' => $inc->id,
                'empresa_nombre' => $inc->empresa?->razon_social ?? 'Global',
                'empresa_ruc' => $inc->empresa?->ruc,
                'tipo' => $inc->tipo,
                'titulo' => $inc->titulo,
                'descripcion' => $inc->descripcion,
                'iniciado_en' => $inc->iniciado_en?->format('d/m/Y H:i'),
                'finalizado_en' => $inc->finalizado_en?->format('d/m/Y H:i'),
                'iniciado_por' => $inc->iniciadoPor?->name,
                'resultado_ensayo' => $inc->resultado_ensayo,
                'estado' => $inc->estado,
                'is_activo' => $inc->isActivo(),
                'is_ensayo' => $inc->isEnsayo(),
            ]);

        return Inertia::render('Admin/Contingencia/Index', [
            'metricas' => [
                'total_empresas' => $totalEmpresas,
                'en_contingencia' => $enContingenciaCount,
                'total_incidentes' => $totalIncidentes,
                'total_ensayos' => $totalEnsayos,
            ],
            'empresas' => $empresas,
            'incidentes' => $incidentes,
        ]);
    }

    /**
     * Permite al Superadministrador activar la contingencia sobre un tenant específico.
     */
    public function activar(Request $request, Empresa $empresa): RedirectResponse
    {
        $validated = $request->validate([
            'mensaje' => ['required', 'string', 'max:1000'],
            'titulo' => ['nullable', 'string', 'max:200'],
            'tipo' => ['nullable', 'string', 'in:mantenimiento,incidente_tecnico,indisponibilidad'],
        ]);

        $this->contingenciaService->activarContingencia(
            $empresa,
            $validated,
            Auth::user()
        );

        return back()->with('success', "Contingencia activada para {$empresa->razon_social}.");
    }

    /**
     * Permite al Superadministrador desactivar la contingencia y normalizar un tenant.
     */
    public function desactivar(Request $request, Empresa $empresa): RedirectResponse
    {
        $validated = $request->validate([
            'resolucion' => ['nullable', 'string', 'max:500'],
        ]);

        $this->contingenciaService->desactivarContingencia(
            $empresa,
            $validated['resolucion'] ?? 'Normalizado por Superadministrador',
            Auth::user()
        );

        return back()->with('success', "Contingencia desactivada para {$empresa->razon_social}.");
    }
}
