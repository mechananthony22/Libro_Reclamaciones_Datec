<?php

namespace App\Http\Middleware;

use App\Enums\RolEmpresa;
use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminEmpresa
{
    /**
     * Handle an incoming request for routes requiring company administration privileges.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            $tenantId = session('current_tenant_id');
            if ($tenantId) {
                $empresa = Empresa::find($tenantId);
                $empresa?->makeCurrent();
            }
        }

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        $esAdmin = $user->empresas()
            ->where('tenants.id', $empresa->id)
            ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
            ->wherePivot('activo', true)
            ->exists();

        if (! $esAdmin) {
            abort(403, 'Acceso denegado: Solo el Administrador de la Empresa puede gestionar usuarios, roles y auditoría.');
        }

        return $next($request);
    }
}
