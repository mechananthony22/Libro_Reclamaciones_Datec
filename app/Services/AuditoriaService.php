<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio centralizado para el registro de eventos en el historial de auditoría de la plataforma.
 * Garantiza que cada operación relevante conserve trazabilidad del actor, tenant, acción y fecha.
 */
class AuditoriaService
{
    /**
     * Registra un evento en la bitácora de auditoría.
     *
     * @param  array{
     *     empresa_id?: int|null,
     *     user?: User|null,
     *     actor_tipo?: string|null,
     *     actor_nombre?: string|null,
     *     actor_email?: string|null,
     *     datos_antes?: array|null,
     *     datos_despues?: array|null,
     *     request?: Request|null,
     *     ip?: string|null,
     *     user_agent?: string|null,
     * }  $opciones
     */
    public function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        array $opciones = []
    ): AuditoriaLog {
        /** @var Request|null $request */
        $request = $opciones['request'] ?? (request() instanceof Request ? request() : null);

        /** @var User|null $usuario */
        $usuario = $opciones['user'] ?? Auth::user();

        // Determinar el tenant asociado
        $empresaId = $opciones['empresa_id'] ?? Empresa::current()?->id;

        // Determinar tipo y nombre del actor
        $actorTipo = $opciones['actor_tipo'] ?? null;
        if (! $actorTipo) {
            if ($usuario) {
                $actorTipo = $usuario->isSuperAdmin() ? 'superadmin' : 'usuario_empresa';
            } else {
                $actorTipo = 'sistema';
            }
        }

        $actorNombre = $opciones['actor_nombre'] ?? ($usuario?->name ?? 'Sistema');
        $actorEmail = $opciones['actor_email'] ?? $usuario?->email;

        $ip = $opciones['ip'] ?? $request?->ip();
        $userAgent = $opciones['user_agent'] ?? ($request ? substr((string) $request->userAgent(), 0, 500) : null);

        return AuditoriaLog::create([
            'empresa_id' => $empresaId,
            'user_id' => $usuario?->id,
            'actor_tipo' => $actorTipo,
            'actor_nombre' => $actorNombre,
            'actor_email' => $actorEmail,
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_antes' => $opciones['datos_antes'] ?? null,
            'datos_despues' => $opciones['datos_despues'] ?? null,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}

