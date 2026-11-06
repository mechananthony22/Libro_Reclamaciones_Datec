<?php

namespace App\Jobs;

use App\Mail\ConstanciaReclamacionMailable;
use App\Models\Reclamacion;
use App\Models\ReclamacionEnvio;
use App\Models\ReclamacionEvento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tarea asíncrona encargada de remitir la constancia de reclamación por correo electrónico.
 * Garantiza que cualquier falla del servidor SMTP no interrumpa la validez de la reclamación
 * y registra el estado, reintentos y error en la tabla de auditoría de envíos.
 */
class EnviarConstanciaReclamacionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos máximos permitidos antes de marcar como fallido.
     */
    public int $tries = 3;

    /**
     * Tiempo de espera en segundos antes de reintentar la tarea.
     */
    public int $backoff = 30;

    /**
     * Crea la instancia de la tarea con el expediente a notificar.
     */
    public function __construct(
        public Reclamacion $reclamacion
    ) {}

    /**
     * Ejecuta la tarea de envío del correo con la constancia PDF adjunta.
     */
    public function handle(): void
    {
        // Restaurar explícitamente el contexto del tenant para el procesamiento asíncrono
        $this->reclamacion->empresa?->makeCurrent();

        $consumidor = $this->reclamacion->datos_consumidor ?? [];
        $destinatario = $consumidor['email'] ?? null;

        if (! $destinatario || ! filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Registrar o actualizar el registro de auditoría de envíos
        $envio = ReclamacionEnvio::firstOrCreate(
            [
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'tipo' => 'constancia_recepcion',
            ],
            [
                'destinatario' => $destinatario,
                'canal' => 'email',
                'estado' => 'pendiente',
                'intentos' => 0,
            ]
        );

        $envio->increment('intentos');

        try {
            // Envío del correo transaccional
            Mail::to($destinatario)->send(new ConstanciaReclamacionMailable($this->reclamacion));

            $envio->update([
                'estado' => 'enviado',
                'enviado_en' => now(),
                'error_mensaje' => null,
            ]);

            // Registrar evento en la auditoría del expediente
            ReclamacionEvento::create([
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'actor_id' => null,
                'actor_nombre' => 'Sistema de Notificaciones',
                'accion' => 'constancia_enviada',
                'descripcion' => "Constancia de {$this->reclamacion->tipo->label()} enviada con éxito al correo {$destinatario}.",
                'datos_antes' => null,
                'datos_despues' => [
                    'destinatario' => $destinatario,
                    'enviado_en' => now()->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            $envio->update([
                'estado' => 'fallido',
                'error_mensaje' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Acciones finales en caso de agotarse todos los intentos de reenvío.
     */
    public function failed(?Throwable $exception): void
    {
        $consumidor = $this->reclamacion->datos_consumidor ?? [];
        $destinatario = $consumidor['email'] ?? 'Desconocido';

        ReclamacionEnvio::where('reclamacion_id', $this->reclamacion->id)
            ->where('tipo', 'constancia_recepcion')
            ->update([
                'estado' => 'fallido',
                'error_mensaje' => $exception?->getMessage() ?: 'Error al contactar servidor SMTP tras múltiples reintentos.',
            ]);

        ReclamacionEvento::create([
            'empresa_id' => $this->reclamacion->empresa_id,
            'reclamacion_id' => $this->reclamacion->id,
            'actor_tipo' => 'sistema',
            'actor_id' => null,
            'actor_nombre' => 'Sistema de Notificaciones',
            'accion' => 'falla_envio_constancia',
            'descripcion' => "No se pudo remitir la constancia al correo {$destinatario}. El reclamo continúa siendo plenamente válido.",
            'datos_antes' => null,
            'datos_despues' => [
                'error' => $exception?->getMessage(),
            ],
            'created_at' => now(),
        ]);
    }
}
