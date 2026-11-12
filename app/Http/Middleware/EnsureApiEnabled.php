<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para gobernar el acceso controlado a la API REST.
 * Asegura que en la versión MVP la API pública permanezca deshabilitada
 * hasta la fase formal de expansión (F2), manteniendo los casos de uso desacoplados.
 */
class EnsureApiEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiHabilitada = (bool) config('services.api.public_enabled', false);

        if (! $apiHabilitada) {
            return response()->json([
                'error' => 'api_no_disponible_en_mvp',
                'mensaje' => 'La API REST pública para integración se encuentra reservada para la fase de expansión (F2) y deshabilitada en la versión MVP.',
                'fase' => 'F2',
                'estado' => 'deshabilitado_en_mvp',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // Si se configuró una API Key global, validar autenticidad de la petición
        $requiredKey = config('services.api.key');
        if ($requiredKey) {
            $apiKey = $request->header('X-API-Key') ?: $request->bearerToken();
            if (! $apiKey || ! hash_equals($requiredKey, $apiKey)) {
                return response()->json([
                    'error' => 'no_autorizado',
                    'mensaje' => 'Se requiere una API Key válida en la cabecera X-API-Key o Authorization: Bearer.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        return $next($request);
    }
}
