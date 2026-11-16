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
 * Notificación enviada a los administradores de la empresa cuando un ingeniero de soporte de Datec
 * solicita acceso delegado temporal para labores de soporte técnico en sus expedientes.
 */
class SolicitudAccesoDelegadoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public SoporteAutorizacion $ticket,
        public ?User $solicitante = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔐 [SOLICITUD DE ACCESO DE SOPORTE] Ticket {$this->ticket->codigo_ticket} - Datec",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud_acceso_delegado',
            with: [
                'empresa' => $this->empresa,
                'ticket' => $this->ticket,
                'solicitante' => $this->solicitante,
                'urlSoporte' => route('empresa.soporte.index'),
            ],
        );
    }
}

