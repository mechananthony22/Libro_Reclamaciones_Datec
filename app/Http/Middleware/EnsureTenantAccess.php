<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request for tenant-isolated administrative routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Debes iniciar sesión para acceder al panel.');
        }

        /** @var int|null $tenantId */
        $tenantId = session('current_tenant_id');

        if (! $tenantId) {
            // Asignar la primera empresa activa a la que pertenece el usuario
            $empresaPivote = $user->empresas()->wherePivot('activo', true)->first();
            if ($empresaPivote) {
                $tenantId = $empresaPivote->id;
                session(['current_tenant_id' => $tenantId]);
            }
        }

        // Si es Superadmin global de Datec
        if ($user->isSuperAdmin()) {
            if ($tenantId) {
                $empresa = Empresa::find($tenantId);
                $empresa?->makeCurrent();
            }

            return $next($request);
        }

        if (! $tenantId) {
            abort(403, 'No tienes ninguna empresa asignada actualmente.');
        }

        // Validar membresía activa del usuario en el tenant (RF-007)
        $empresaAutorizada = $user->empresas()
            ->where('tenants.id', $tenantId)
            ->wherePivot('activo', true)
            ->first();

        if (! $empresaAutorizada) {
            abort(403, 'Acceso denegado: no estás autorizado para interactuar con esta empresa.');
        }

        if (! $empresaAutorizada->isActiva()) {
            abort(403, 'El servicio de tu empresa se encuentra temporalmente suspendido o inactivo.');
        }

        // Establecer el tenant actual en el contexto de la aplicación
        $empresaAutorizada->makeCurrent();

        return $next($request);
    }
}

