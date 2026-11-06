<?php

namespace App\Http\Middleware;

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;
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

                $currentTenant?->makeCurrent();
            }
        }

        if (! $currentTenant) {
            abort(404, 'Empresa no encontrada.');
        }

        if (! $currentTenant->activo || $currentTenant->estado !== EstadoEmpresa::Activa) {
            if ($currentTenant->estado === EstadoEmpresa::Suspendida) {
                abort(403, 'El servicio de este Libro de Reclamaciones se encuentra temporalmente suspendido.');
            }

            if ($currentTenant->estado === EstadoEmpresa::Borrador) {
                abort(404, 'Este Libro de Reclamaciones aún no se encuentra publicado.');
            }

            abort(404, 'Empresa no disponible.');
        }

        return $next($request);
    }
}
