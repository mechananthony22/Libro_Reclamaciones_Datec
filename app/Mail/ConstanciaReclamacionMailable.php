<?php

namespace App\Mail;

use App\Models\Reclamacion;
use App\Services\PdfConstanciaService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación transaccional formal enviada al consumidor tras registrar su reclamo o queja.
 * Incluye la constancia en formato PDF oficial como archivo adjunto.
 */
class ConstanciaReclamacionMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crea una nueva instancia del mensaje con el expediente de reclamación.
     */
    public function __construct(
        public Reclamacion $reclamacion
    ) {}

    /**
     * Define el asunto y remitente del sobre del correo.
     */
    public function envelope(): Envelope
    {
        $empresa = $this->reclamacion->empresa;
        $tipoLabel = strtoupper($this->reclamacion->tipo->label());

        return new Envelope(
            subject: "Constancia de {$tipoLabel} N° {$this->reclamacion->numero_hoja} - {$empresa->razon_social}",
        );
    }

    /**
     * Define la vista Blade del cuerpo del correo.
     */
    public function content(): Content
    {
        $empresa = $this->reclamacion->empresa;
        $consumidor = $this->reclamacion->datos_consumidor ?? [];
        $urlDescargaPdf = route('libro.constancia.pdf', [
            'slug' => $empresa->slug,
            'token' => $this->reclamacion->token_consulta,
        ]);

        return new Content(
            view: 'emails.constancia_reclamacion',
            with: [
                'reclamacion' => $this->reclamacion,
                'empresa' => $empresa,
                'consumidor' => $consumidor,
                'urlDescargaPdf' => $urlDescargaPdf,
            ]
        );
    }

    /**
     * Adjunta la constancia oficial en formato PDF al correo.
     */
    public function attachments(): array
    {
        $pdf = app(PdfConstanciaService::class)->generarPdf($this->reclamacion);
        $nombreArchivo = "Constancia_Reclamacion_{$this->reclamacion->numero_hoja}.pdf";

        return [
            Attachment::fromData(fn () => $pdf->output(), $nombreArchivo)
                ->withMime('application/pdf'),
        ];
    }
}
