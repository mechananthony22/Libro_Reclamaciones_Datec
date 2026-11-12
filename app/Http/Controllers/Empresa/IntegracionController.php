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
<a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:10px;padding:10px 20px;background:#ffffff;color:#1e293b;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px 0 rgba(0,0,0,0.05);text-decoration:none;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:700;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
  <span>Libro de Reclamaciones</span>
</a>
HTML,
            'enlace_texto' => <<<HTML
<!-- Enlace Texto Estándar para Footer de Comercio Electrónico -->
<a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" style="color:#2563eb;text-decoration:underline;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:700;" title="Libro de Reclamaciones Virtual conforme a Ley 29571">
  Libro de Reclamaciones
</a>
HTML,
            'widget_flotante' => <<<HTML
<!-- Botón Flotante Fijo de Libro de Reclamaciones -->
<div style="position:fixed;bottom:20px;right:20px;z-index:9999;">
  <a href="{$urlLibro}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;background:{$color};color:#ffffff;padding:10px 18px;border-radius:9999px;box-shadow:0 4px 14px rgba(37,99,235,0.25);text-decoration:none;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:700;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
    <span>Libro de Reclamaciones</span>
  </a>
</div>
HTML,
            'enlace_consulta' => <<<HTML
<!-- Enlace Directo para Seguimiento y Consulta de Estado -->
<a href="{$urlConsulta}" target="_blank" rel="noopener noreferrer" style="color:#1e293b;text-decoration:underline;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:700;">
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
