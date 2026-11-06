<?php

namespace App\Mail;

use App\Models\Reclamacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación preventiva o de alerta enviada al responsable y administradores
 * de la empresa cuando una reclamación está próxima a vencer o ya venció.
 */
class AlertaVencimientoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reclamacion $reclamacion,
        public array $semaforo
    ) {}

    public function envelope(): Envelope
    {
        $hoja = $this->reclamacion->numero_hoja;
        $esVencido = $this->semaforo['vencido'] ?? false;
        $prefijo = $esVencido ? '⚠️ [VENCIDO]' : '⏳ [URGENTE]';

        return new Envelope(
            subject: "{$prefijo} Alerta de Plazo Legal - Hoja N° {$hoja} ({$this->semaforo['etiqueta']})",
        );
    }

    public function content(): Content
    {
        $empresa = $this->reclamacion->empresa;
        $urlExpediente = route('empresa.expedientes.show', ['id' => $this->reclamacion->id]);

        return new Content(
            view: 'emails.alerta_vencimiento',
            with: [
                'reclamacion' => $this->reclamacion,
                'empresa' => $empresa,
                'semaforo' => $this->semaforo,
                'esVencido' => $this->semaforo['vencido'] ?? false,
                'urlExpediente' => $urlExpediente,
            ],
        );
    }
}
