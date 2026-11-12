<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para el envío del código de 6 dígitos para recuperación de contraseña.
 */
class CodigoRecuperacionPasswordMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $codigo,
        public string $userName,
        public int $minutosExpiracion = 15
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔐 Código de recuperación de contraseña: {$this->codigo} - DatecLibro",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo_recuperacion_password',
            with: [
                'codigo' => $this->codigo,
                'userName' => $this->userName,
                'minutosExpiracion' => $this->minutosExpiracion,
            ],
        );
    }
}
