<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que asegura que únicamente los usuarios con rol global de Superadministrador
 * de Datec puedan acceder a las rutas de gestión de la plataforma y monitoreo global.
 */
class EnsureSuperAdmin
{
    /**
     * Valida que el usuario autenticado sea Superadministrador Datec.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isSuperAdmin()) {
            abort(403, 'Acceso denegado. Se requieren privilegios globales de Superadministrador Datec.');
        }

        return $next($request);
    }
}
