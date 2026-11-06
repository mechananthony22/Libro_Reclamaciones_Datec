<?php

namespace App\Mail;

use App\Models\Reclamacion;
use App\Models\ReclamacionRespuesta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Notificación formal de atención y respuesta emitida al consumidor.
 * Adjunta los documentos probatorios que la empresa haya anexado a la respuesta.
 */
class RespuestaReclamacionMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reclamacion $reclamacion,
        public ReclamacionRespuesta $respuesta
    ) {}

    public function envelope(): Envelope
    {
        $empresa = $this->reclamacion->empresa;
        $tipoLabel = strtoupper($this->reclamacion->tipo->label());

        return new Envelope(
            subject: "Respuesta Formal a su {$tipoLabel} N° {$this->reclamacion->numero_hoja} - {$empresa->razon_social}",
        );
    }

    public function content(): Content
    {
        $empresa = $this->reclamacion->empresa;
        $consumidor = $this->reclamacion->datos_consumidor ?? [];

        // Generar enlace firmado temporal de 30 días para visualización del dictamen
        $urlConsultaSegura = URL::temporarySignedRoute(
            'libro.consulta.firmada',
            now()->addDays(30),
            [
                'slug' => $empresa->slug,
                'token' => $this->reclamacion->token_consulta,
            ]
        );

        return new Content(
            view: 'emails.respuesta_reclamacion',
            with: [
                'reclamacion' => $this->reclamacion,
                'respuesta' => $this->respuesta,
                'empresa' => $empresa,
                'consumidor' => $consumidor,
                'urlConsultaSegura' => $urlConsultaSegura,
            ],
        );
    }

    /**
     * Adjunta los archivos probatorios de la respuesta formal si existen.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $adjuntos = [];
        $rutas = $this->respuesta->adjuntos_paths ?? [];
        $prefijoEsperado = "empresas/{$this->reclamacion->empresa_id}/";

        foreach ($rutas as $item) {
            $path = is_array($item) ? ($item['path'] ?? '') : (string) $item;
            $nombreOriginal = is_array($item) ? ($item['nombre'] ?? basename($path)) : basename($path);

            // Aislamiento multitenant estricto: descartar cualquier ruta que no pertenezca al directorio del tenant
            if ($path !== '' && str_starts_with($path, $prefijoEsperado) && Storage::disk('local')->exists($path)) {
                $adjuntos[] = Attachment::fromStorageDisk('local', $path)
                    ->as($nombreOriginal);
            }
        }

        return $adjuntos;
    }
}
