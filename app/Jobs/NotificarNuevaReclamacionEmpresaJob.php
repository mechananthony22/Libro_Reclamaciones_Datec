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
        $empresa = $this->reclamacion->empresa;
        if (! $empresa) {
            return;
        }

        $empresa->makeCurrent();

        // 1. Respetar la configuración de la empresa: no enviar si la alerta está desactivada
        if (! $empresa->notificar_nuevo_reclamo) {
            Log::info("La empresa ID {$empresa->id} ({$empresa->razon_social}) tiene desactivada la notificación por correo de nuevos reclamos.");

            return;
        }

        // 2. Obtener los destinatarios configurados (empresa, representante y correos adicionales)
        $destinatarios = $empresa->destinatariosNotificaciones();
        if (empty($destinatarios)) {
            Log::info("No hay destinatarios de notificación configurados para la empresa ID {$empresa->id}.");

            return;
        }

        // 3. Registrar previamente los intentos en la tabla de envíos con las columnas exactas del esquema
        $envios = [];
        foreach ($destinatarios as $email) {
            $envios[] = ReclamacionEnvio::firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'reclamacion_id' => $this->reclamacion->id,
                    'tipo' => 'alerta_nueva_empresa',
                    'destinatario' => $email,
                ],
                [
                    'canal' => 'email',
                    'estado' => 'pendiente',
                    'intentos' => 0,
                ]
            );
        }

        foreach ($envios as $envio) {
            $envio->increment('intentos');
        }

        $mailable = new NuevaReclamacionEmpresaMailable($this->reclamacion);

        // 4. Resguardar la privacidad de correos: primer buzón en 'to' y buzones adicionales en copia oculta 'bcc'
        $destinatarioPrincipal = $destinatarios[0];
        $destinatariosCopiaOculta = array_slice($destinatarios, 1);

        try {
            $mailer = Mail::to($destinatarioPrincipal);
            if (! empty($destinatariosCopiaOculta)) {
                $mailer->bcc($destinatariosCopiaOculta);
            }
            $mailer->send($mailable);

            // 5. Confirmar entrega exitosa en la base de datos
            $ahora = now();
            foreach ($envios as $envio) {
                $envio->update([
                    'estado' => 'enviado',
                    'enviado_en' => $ahora,
                    'error_mensaje' => null,
                ]);
            }

            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $this->reclamacion->id,
                'actor_tipo' => 'sistema',
                'actor_nombre' => 'Sistema de Alertas',
                'accion' => 'notificacion_empresa_enviada',
                'descripcion' => 'Alerta de nuevo reclamo remitida formalmente a: '.implode(', ', $destinatarios),
                'created_at' => $ahora,
            ]);
        } catch (Throwable $e) {
            // 6. Registrar el fallo en los registros de envío para trazabilidad
            $mensajeError = substr($e->getMessage(), 0, 1000);
            foreach ($envios as $envio) {
                $envio->update([
                    'estado' => 'fallido',
                    'error_mensaje' => $mensajeError,
                ]);
            }

            Log::error('Fallo al remitir alerta de reclamación a la empresa: '.$e->getMessage(), [
                'reclamacion_id' => $this->reclamacion->id,
                'destinatarios' => $destinatarios,
            ]);

            throw $e;
        }
    }
}
