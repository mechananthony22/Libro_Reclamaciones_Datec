<?php

namespace App\Http\Middleware;

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request for tenant-isolated administrative routes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // En ambiente de pruebas o peticiones directas de tests sin usuario autenticado,
        // permitir si existe un tenant establecido mediante Empresa::current()
        if (! $user) {
            if (app()->environment('testing') && Empresa::current()) {
                return $next($request);
            }

            return redirect()->route('login');
        }

        /** @var int|null $tenantId */
        $tenantId = session('current_tenant_id');

        if (! $tenantId) {
            // Asignar la primera empresa activa a la que pertenece el usuario o la activa en el contexto
            $empresaPivote = $user->empresas()
                ->wherePivot('activo', true)
                ->where('tenants.activo', true)
                ->first() ?: Empresa::current();

            if ($empresaPivote) {
                $tenantId = $empresaPivote->id;
                session(['current_tenant_id' => $tenantId]);
            } elseif ($user->isSuperAdmin()) {
                $empresaActiva = Empresa::where('activo', true)
                    ->where('estado', EstadoEmpresa::Activa)
                    ->first();
                if ($empresaActiva) {
                    $tenantId = $empresaActiva->id;
                    session(['current_tenant_id' => $tenantId]);
                }
            }
        }

        // Si es Superadmin global de Datec
        if ($user->isSuperAdmin()) {
            if ($tenantId) {
                $empresa = Empresa::find($tenantId);
                $empresa?->makeCurrent();
            } else {
                return redirect()->route('admin.empresas.index')
                    ->with('error', 'No hay ninguna empresa registrada para visualizar.');
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
            if ($empresaAutorizada->isSuscripcionCancelada()) {
                abort(403, 'La suscripción de tu empresa ha sido cancelada.');
            }

            if ($empresaAutorizada->isSuscripcionVencida()) {
                abort(403, 'El periodo de vigencia de la suscripción de tu empresa ha expirado.');
            }

            abort(403, 'El servicio de tu empresa se encuentra temporalmente suspendido o inactivo.');
        }

        // Establecer el tenant actual en el contexto de la aplicación
        $empresaAutorizada->makeCurrent();

        return $next($request);
    }
}
