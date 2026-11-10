<?php

namespace App\Http\Middleware;

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;
use App\Services\SlugReconfigurationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantBySlug
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Empresa|null $currentTenant */
        $currentTenant = Empresa::current();

        if (! $currentTenant) {
            $slug = $request->route('slug') ?? $request->segment(2);

            if ($slug) {
                $currentTenant = Empresa::query()
                    ->where('slug', $slug)
                    ->first();

                // Si no se encuentra como slug vigente, verificar si corresponde a un slug histórico para redirección 301
                if (! $currentTenant) {
                    $empresaHistorica = app(SlugReconfigurationService::class)->obtenerRedireccionSiExiste($slug);
                    if ($empresaHistorica) {
                        $currentUri = $request->getRequestUri();
                        $targetUri = preg_replace('#^/l/'.preg_quote($slug, '#').'(\b|/|$)#', '/l/'.$empresaHistorica->slug.'$1', $currentUri);

                        return redirect($targetUri, 301);
                    }
                }

                // Si no se encuentra como slug vigente, verificar si corresponde a un slug histórico para redirección 301
                if (! $currentTenant) {
                    $empresaHistorica = app(SlugReconfigurationService::class)->obtenerRedireccionSiExiste($slug);
                    if ($empresaHistorica) {
                        $currentUri = $request->getRequestUri();
                        $targetUri = preg_replace('#^/l/'.preg_quote($slug, '#').'(\b|/|$)#', '/l/'.$empresaHistorica->slug.'$1', $currentUri);

                        return redirect($targetUri, 301);
                    }
                }

                $currentTenant?->makeCurrent();
            }
        }

        if (! $currentTenant) {
            abort(404, 'Empresa no encontrada.');
        }

        if (! $currentTenant->activo || $currentTenant->estado !== EstadoEmpresa::Activa) {
            if ($currentTenant->estado === EstadoEmpresa::BajaProgramada) {
                abort(403, 'El servicio de este Libro de Reclamaciones ha concluido. La información se encuentra bajo custodia y política de retención legal de pruebas.');
            }

            if ($currentTenant->estado === EstadoEmpresa::Suspendida) {
                abort(403, 'El servicio de este Libro de Reclamaciones se encuentra temporalmente suspendido.');
            }

            if ($currentTenant->estado === EstadoEmpresa::Borrador) {
                abort(404, 'Este Libro de Reclamaciones aún no se encuentra publicado.');
            }

            abort(404, 'Empresa no disponible.');
        }

        if ($currentTenant->estaEnContingencia() && ! $request->isMethodSafe()) {
            abort(503, $currentTenant->contingencia_mensaje ?: 'El servicio de registro se encuentra temporalmente en contingencia. Por favor utilice nuestros canales alternativos.');
        }

        return $next($request);
    }
}
