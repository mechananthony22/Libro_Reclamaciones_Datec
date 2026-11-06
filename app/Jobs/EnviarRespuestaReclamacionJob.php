<?php

namespace App\Jobs;

use App\Mail\RespuestaReclamacionMailable;
use App\Models\Reclamacion;
use App\Models\ReclamacionEnvio;
use App\Models\ReclamacionEvento;
use App\Models\ReclamacionRespuesta;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Trabajo en segundo plano para notificar formalmente la respuesta de una reclamación al consumidor.
 * Maneja reintentos automáticos, registra fallas y actualiza la bitácora inmutable.
 */
class EnviarRespuestaReclamacionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Reclamacion $reclamacion,
        public ReclamacionRespuesta $respuesta
    ) {}

    public function handle(): void
    {
        // Restaurar explícitamente el contexto del tenant para el procesamiento asíncrono
        $this->reclamacion->empresa?->makeCurrent();

        $consumidor = $this->reclamacion->datos_consumidor ?? [];
        $emailDestino = trim((string) ($consumidor['email'] ?? ''));

        // Si el consumidor no registró un correo válido, dejar evidencia explícita
        // del impedimento en lugar de omitir silenciosamente la notificación.
        if ($emailDestino === '' || ! filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            ReclamacionEnvio::updateOrCreate(
                [
                    'empresa_id' => $this->reclamacion->empresa_id,
                    'reclamacion_id' => $this->reclamacion->id,
                    'tipo' => 'respuesta_formal',
                    'destinatario' => '(sin correo registrado)',
                ],
                [
                    'canal' => 'email',
                    'estado' => 'fallido',
                    'intentos' => 0,
                    'error_mensaje' => 'El consumidor no registró un correo electrónico válido para recibir la respuesta formal.',
                ]
            );

            ReclamacionEvento::create([
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'actor_nombre' => 'Sistema de Notificaciones',
                'accion' => 'notificacion_respuesta_sin_destinatario',
                'descripcion' => 'No se pudo notificar la respuesta formal porque el consumidor no registró un correo electrónico válido.',
                'created_at' => now(),
            ]);

            return;
        }

        // Registrar o recuperar el registro de auditoría de envío
        $envio = ReclamacionEnvio::firstOrCreate(
            [
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'tipo' => 'respuesta_formal',
                'destinatario' => $emailDestino,
            ],
            [
                'canal' => 'email',
                'estado' => 'pendiente',
                'intentos' => 0,
            ]
        );

        $envio->increment('intentos');

        try {
            Mail::to($emailDestino)->send(
                new RespuestaReclamacionMailable($this->reclamacion, $this->respuesta)
            );

            $envio->update([
                'estado' => 'enviado',
                'enviado_en' => now(),
                'error_mensaje' => null,
            ]);

            $this->respuesta->update([
                'notificado' => true,
                'notificado_en' => now(),
            ]);

            // Registrar evento de auditoría de envío exitoso
            ReclamacionEvento::create([
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'accion' => 'notificacion_respuesta_enviada',
                'descripcion' => "Respuesta formal notificada con éxito por correo electrónico a {$emailDestino}.",
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            $envio->update([
                'estado' => 'fallido',
                'error_mensaje' => $e->getMessage(),
            ]);

            ReclamacionEvento::create([
                'empresa_id' => $this->reclamacion->empresa_id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'accion' => 'notificacion_respuesta_fallida',
                'descripcion' => "Fallo al notificar respuesta formal a {$emailDestino}. Error: {$e->getMessage()}",
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Acciones finales cuando se agotan los reintentos automáticos de notificación.
     */
    public function failed(?Throwable $exception): void
    {
        ReclamacionEnvio::where('empresa_id', $this->reclamacion->empresa_id)
            ->where('reclamacion_id', $this->reclamacion->id)
            ->where('tipo', 'respuesta_formal')
            ->update([
                'estado' => 'fallido',
                'error_mensaje' => $exception?->getMessage() ?: 'No fue posible entregar la respuesta formal tras agotar los reintentos.',
            ]);

        ReclamacionEvento::create([
            'empresa_id' => $this->reclamacion->empresa_id,
            'reclamacion_id' => $this->reclamacion->id,
            'actor_tipo' => 'sistema',
            'actor_nombre' => 'Sistema de Notificaciones',
            'accion' => 'notificacion_respuesta_fallida_definitiva',
            'descripcion' => 'La notificación de la respuesta formal agotó sus reintentos y requiere intervención manual desde la ficha del expediente.',
            'datos_despues' => [
                'error' => $exception?->getMessage(),
                'intentos' => $this->tries,
            ],
            'created_at' => now(),
        ]);
    }
}
