<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\SoporteAutorizacion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación enviada al equipo de soporte de Datec cuando una empresa cliente
 * autoriza o rechaza una solicitud de acceso delegado temporal.
 */
class RespuestaAccesoDelegadoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public SoporteAutorizacion $ticket,
        public User $actor,
        public string $accion // 'autorizado' | 'rechazado'
    ) {}

    public function envelope(): Envelope
    {
        $estadoTag = strtoupper($this->accion);
        $icono = $this->accion === 'autorizado' ? '✅' : '🚫';

        return new Envelope(
            subject: "{$icono} [ACCESO {$estadoTag}] Ticket {$this->ticket->codigo_ticket} - {$this->empresa->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.respuesta_acceso_delegado',
            with: [
                'empresa' => $this->empresa,
                'ticket' => $this->ticket,
                'actor' => $this->actor,
                'accion' => $this->accion,
                'urlSoporte' => route('admin.soporte.index'),
            ],
        );
    }
}
