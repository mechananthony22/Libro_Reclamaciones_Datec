<?php

namespace App\Mail;

use App\Models\PlataformaAlerta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación formal por correo electrónico dirigida al equipo de operaciones y soporte de Datec
 * ante la detección de anomalías operativas, saturación de colas o fallos de infraestructura.
 */
class AlertaOperativaDatecMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlataformaAlerta $alerta
    ) {}

    public function envelope(): Envelope
    {
        $severidadTag = strtoupper($this->alerta->severidad);
        $icono = $this->alerta->isCritica() ? '🚨 [CRÍTICA]' : '⚠️ [ALERTA]';

        return new Envelope(
            subject: "{$icono} {$severidadTag}: {$this->alerta->titulo} - Libro Reclamaciones Datec",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta_operativa_datec',
            with: [
                'alerta' => $this->alerta,
                'fechaHora' => now()->format('d/m/Y H:i:s'),
                'urlDashboard' => route('admin.monitoreo.index'),
            ],
        );
    }
}
