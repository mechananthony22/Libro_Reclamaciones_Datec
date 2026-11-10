<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaSlugHistorial;
use App\Services\SlugReconfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la publicación de enlaces de integración en e-commerce y reconfiguración
 * de identificadores permanentes (slugs) con preservación de redirecciones 301.
 */
class IntegracionController extends Controller
{
    public function __construct(
        protected SlugReconfigurationService $slugService
    ) {}

    /**
     * Muestra la interfaz de enlaces de integración, fragmentos HTML, guías de instalación y gestión del slug.
     */
    public function index(): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $urlLibro = url("/l/{$empresa->slug}");
        $urlConsulta = url("/l/{$empresa->slug}/consultar");
        $color = $empresa->color_primario ?: '#2563EB';

        $slugsHistoricos = $empresa->slugsHistoricos()
            ->with('cambiadoPor:id,name')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (EmpresaSlugHistorial $item) => [
                'id' => $item->id,
                'slug_antiguo' => $item->slug_antiguo,
                'url_antigua' => url("/l/{$item->slug_antiguo}"),
                'motivo' => $item->motivo,
                'cambiado_por' => $item->cambiadoPor?->name ?? 'Administración',
                'fecha' => $item->created_at?->format('d/m/Y H:i'),
            ]);

        $snippets = [
            'boton_estandar' => <<<HTML
<!-- Enlace Oficial al Libro de Reclamaciones (DatecLibro) -->
<a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#f8fafc;color:#0f172a;border:1px solid #cbd5e1;border-radius:8px;text-decoration:none;font-family:system-ui,sans-serif;font-size:12px;font-weight:600;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
  <span>Libro de Reclamaciones</span>
</a>
HTML,
            'enlace_texto' => <<<HTML
<!-- Enlace Texto Estándar para Footer de Comercio Electrónico -->
<a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" class="enlace-libro-reclamaciones" title="Libro de Reclamaciones Virtual conforme a Ley 29571">
  Libro de Reclamaciones
</a>
HTML,
            'widget_flotante' => <<<HTML
<!-- Botón Flotante Fijo de Libro de Reclamaciones -->
<div style="position:fixed;bottom:20px;right:20px;z-index:9999;">
  <a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:8px;background:{$color};color:#ffffff;padding:10px 18px;border-radius:50px;box-shadow:0 4px 14px rgba(0,0,0,0.18);text-decoration:none;font-family:system-ui,sans-serif;font-size:13px;font-weight:700;">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
    <span>Libro de Reclamaciones</span>
  </a>
</div>
HTML,
            'enlace_consulta' => <<<HTML
<!-- Enlace Directo para Seguimiento y Consulta de Estado -->
<a href="{$urlConsulta}" target="_blank" rel="noopener noreferrer" class="enlace-consulta-reclamos">
  Consultar estado de mi reclamo o queja
</a>
HTML,
        ];

        return Inertia::render('Empresa/Integracion/Index', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'slug' => $empresa->slug,
                'color_primario' => $color,
            ],
            'urls' => [
                'libro' => $urlLibro,
                'consulta' => $urlConsulta,
            ],
            'snippets' => $snippets,
            'slugs_historicos' => $slugsHistoricos,
        ]);
    }

    /**
     * Procesa la reconfiguración del slug asegurando la reserva del slug anterior
     * y configurando la redirección permanente 301.
     */
    public function cambiarSlug(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $validated = $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones medios simples.',
        ]);

        $this->slugService->cambiarSlug(
            $empresa,
            $validated['slug'],
            $validated['motivo'] ?? null,
            Auth::user()
        );

        return back()->with('success', 'El enlace permanente (slug) se ha reconfigurado exitosamente. La dirección anterior redirige de forma permanente (301) a la nueva URL.');
    }

    /**
     * Consulta asíncrona de disponibilidad de slug para validación en tiempo real en la UI.
     */
    public function verificarDisponibilidad(Request $request): JsonResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $slug = (string) $request->input('slug', '');
        $valido = $this->slugService->esSlugValido($slug);
        $disponible = $valido && $this->slugService->esSlugDisponible($slug, $empresa->id);

        return response()->json([
            'valido' => $valido,
            'disponible' => $disponible,
            'slug_formateado' => Str::slug($slug),
            'mensaje' => $disponible
                ? 'El enlace permanente se encuentra disponible.'
                : ($valido ? 'El enlace permanente ya se encuentra en uso o reservado para otra empresa.' : 'Formato de slug no permitido o palabra reservada.'),
        ]);
    }
}
