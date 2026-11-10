<?php

namespace App\Http\Controllers\Empresa;

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
 * Controlador de contingencia operativa y mantenimiento para administradores de empresa.
 * Permite activar la pantalla de contingencia pública, definir canales alternativos y
 * registrar simulacros preventivos de recuperación (DR Rehearsal).
 */
class ContingenciaController extends Controller
{
    public function __construct(
        protected ContingenciaService $contingenciaService
    ) {}

    /**
     * Muestra el panel de control de contingencia y el historial de incidentes y ensayos.
     */
    public function index(): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $incidentes = ContingenciaIncidente::where('empresa_id', $empresa->id)
            ->with(['iniciadoPor:id,name,email', 'finalizadoPor:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->map(fn (ContingenciaIncidente $inc) => [
                'id' => $inc->id,
                'tipo' => $inc->tipo,
                'titulo' => $inc->titulo,
                'descripcion' => $inc->descripcion,
                'canal_alternativo' => $inc->canal_alternativo,
                'iniciado_en' => $inc->iniciado_en?->format('d/m/Y H:i'),
                'finalizado_en' => $inc->finalizado_en?->format('d/m/Y H:i'),
                'iniciado_por' => $inc->iniciadoPor?->name,
                'finalizado_por' => $inc->finalizadoPor?->name,
                'resultado_ensayo' => $inc->resultado_ensayo,
                'estado' => $inc->estado,
                'is_activo' => $inc->isActivo(),
                'is_ensayo' => $inc->isEnsayo(),
            ]);

        return Inertia::render('Empresa/Contingencia/Index', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'en_contingencia' => (bool) $empresa->en_contingencia,
                'contingencia_mensaje' => $empresa->contingencia_mensaje,
                'contingencia_canal_alternativo' => $empresa->canalesContingencia(),
                'contingencia_activada_en' => $empresa->contingencia_activada_en?->format('d/m/Y H:i'),
                'contingencia_estimada_fin' => $empresa->contingencia_estimada_fin?->format('d/m/Y H:i'),
            ],
            'incidentes' => $incidentes,
        ]);
    }

    /**
     * Activa el estado de contingencia técnica para el portal público.
     */
    public function activar(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:200'],
            'tipo' => ['nullable', 'string', 'in:mantenimiento,incidente_tecnico,indisponibilidad'],
            'mensaje' => ['required', 'string', 'max:1000'],
            'estimada_fin' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion_fisica' => ['nullable', 'string', 'max:250'],
            'horario' => ['nullable', 'string', 'max:150'],
        ]);

        $canales = [
            'email' => $validated['email'] ?? $empresa->email,
            'telefono' => $validated['telefono'] ?? $empresa->telefono,
            'direccion_fisica' => $validated['direccion_fisica'] ?? $empresa->direccion,
            'horario' => $validated['horario'] ?? 'Lunes a Viernes de 09:00 a 18:00',
        ];

        $this->contingenciaService->activarContingencia($empresa, [
            'titulo' => $validated['titulo'] ?? 'Activación de plan de contingencia',
            'tipo' => $validated['tipo'] ?? 'mantenimiento',
            'mensaje' => $validated['mensaje'],
            'estimada_fin' => $validated['estimada_fin'] ?? null,
            'canales' => $canales,
        ], Auth::user());

        return back()->with('success', 'Plan de contingencia activado. El portal público mostrará el aviso y canales alternativos.');
    }

    /**
     * Desactiva el modo de contingencia y reanuda el registro público ordinario.
     */
    public function desactivar(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $validated = $request->validate([
            'resolucion' => ['nullable', 'string', 'max:500'],
        ]);

        $this->contingenciaService->desactivarContingencia(
            $empresa,
            $validated['resolucion'] ?? null,
            Auth::user()
        );

        return back()->with('success', 'Modo de contingencia finalizado. El registro del Libro de Reclamaciones está normalizado.');
    }

    /**
     * Ejecuta y asienta un simulacro o ensayo preventivo de recuperación de desastres.
     */
    public function ejecutarEnsayo(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'resultado' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->contingenciaService->ejecutarEnsayo($empresa, $validated, Auth::user());

        return back()->with('success', 'Simulacro y ensayo de recuperación registrado exitosamente en la bitácora sin emitir registros falsos.');
    }
}
