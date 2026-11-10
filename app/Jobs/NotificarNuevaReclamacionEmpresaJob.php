<?php

namespace App\Jobs;

use App\Mail\NuevaReclamacionEmpresaMailable;
use App\Models\Reclamacion;
use App\Models\ReclamacionEnvio;
use App\Models\ReclamacionEvento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tarea asíncrona que notifica a la empresa y a sus buzones configurados
 * sobre la recepción inmediata de una nueva reclamación o queja.
 */
class NotificarNuevaReclamacionEmpresaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Reclamacion $reclamacion
    ) {}

    public function handle(): void
    {
        $this->reclamacion->empresa?->makeCurrent();

        $empresa = $this->reclamacion->empresa;
        if (! $empresa) {
            return;
        }

        // Obtener todos los destinatarios configurados (empresa, representante y emails_notificaciones)
        $destinatarios = $empresa->destinatariosNotificaciones();

        if (empty($destinatarios)) {
            Log::info("No hay destinatarios de notificación configurados para la empresa ID {$empresa->id}.");

            return;
        }

        $mailable = new NuevaReclamacionEmpresaMailable($this->reclamacion);

        try {
            Mail::to($destinatarios)->send($mailable);

            // Registrar auditoría de envío
            foreach ($destinatarios as $email) {
                ReclamacionEnvio::create([
                    'empresa_id' => $empresa->id,
                    'reclamacion_id' => $this->reclamacion->id,
                    'canal' => 'email',
                    'destinatario' => $email,
                    'tipo_mensaje' => 'alerta_nueva_reclamacion_empresa',
                    'estado' => 'enviado',
                    'intentos' => 1,
                    'enviado_at' => now(),
                    'metadata' => [
                        'asunto' => $mailable->envelope()->subject,
                        'rol' => 'empresa_notificacion',
                    ],
                ]);
            }

            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'actor_nombre' => 'Sistema de Alertas',
                'accion' => 'notificacion_empresa_enviada',
                'descripcion' => 'Notificación de nuevo reclamo remitida a los correos de la empresa: '.implode(', ', $destinatarios),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Fallo al remitir alerta de reclamación a la empresa: '.$e->getMessage(), [
                'reclamacion_id' => $this->reclamacion->id,
                'destinatarios' => $destinatarios,
            ]);

            throw $e;
        }
    }
}
