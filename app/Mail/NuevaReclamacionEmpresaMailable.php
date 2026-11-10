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
 * Correo de alerta inmediata para la empresa y destinatarios configurados
 * informando sobre la recepción de un nuevo reclamo o queja.
 */
class NuevaReclamacionEmpresaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reclamacion $reclamacion
    ) {}

    public function envelope(): Envelope
    {
        $tipoLabel = strtoupper($this->reclamacion->tipo->label());
        $empresaNombre = $this->reclamacion->empresa?->name ?? 'Empresa';

        return new Envelope(
            subject: "🚨 [NUEVO {$tipoLabel}] Hoja N° {$this->reclamacion->numero_hoja} - {$empresaNombre}",
        );
    }

    public function content(): Content
    {
        $empresa = $this->reclamacion->empresa;
        $consumidor = $this->reclamacion->datos_consumidor ?? [];
        $urlExpediente = route('empresa.expedientes.show', $this->reclamacion->id);

        return new Content(
            view: 'emails.nueva_reclamacion_empresa',
            with: [
                'reclamacion' => $this->reclamacion,
                'empresa' => $empresa,
                'consumidor' => $consumidor,
                'urlExpediente' => $urlExpediente,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
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
