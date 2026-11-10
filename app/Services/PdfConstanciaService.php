<?php

namespace App\Services;

use App\Models\Reclamacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Servicio encargado de la renderización y generación de la constancia digital en formato PDF
 * de la hoja de reclamaciones, cumpliendo con los estándares de formato y campos de INDECOPI.
 */
class PdfConstanciaService
{
    /**
     * Genera el archivo binario PDF o descarga directa para la reclamación indicada.
     */
    public function generarPdf(Reclamacion $reclamacion)
    {
        $empresa = $reclamacion->empresa;
        $consumidor = $reclamacion->datos_consumidor ?? [];
        $proveedorSnapshot = $reclamacion->datos_legales_snapshot ?? [];

        $logoBase64 = null;
        if (! empty($empresa->logo_path)) {
            $relativePath = str_replace('/storage/', '', $empresa->logo_path);
            $fullPath = storage_path('app/public/'.$relativePath);
            if (file_exists($fullPath)) {
                $fileContent = @file_get_contents($fullPath);
                if ($fileContent !== false) {
                    $mimeType = @mime_content_type($fullPath) ?: 'image/png';
                    $logoBase64 = 'data:'.$mimeType.';base64,'.base64_encode($fileContent);
                }
            }
        }

        $colorPrimario = ! empty($empresa->color_primario) ? $empresa->color_primario : '#1e40af';
        $ultimaRespuesta = $reclamacion->respuestas()->latest('version')->first();

        $pdf = Pdf::loadView('pdf.constancia_reclamacion', [
            'reclamacion' => $reclamacion,
            'empresa' => $empresa,
            'consumidor' => $consumidor,
            'proveedor' => $proveedorSnapshot,
            'respuesta' => $ultimaRespuesta,
            'logoBase64' => $logoBase64,
            'colorPrimario' => $colorPrimario,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Retorna una respuesta de descarga HTTP para el navegador.
     */
    public function descargarPdf(Reclamacion $reclamacion): Response
    {
        $pdf = $this->generarPdf($reclamacion);
        $nombreArchivo = "Constancia_Reclamacion_{$reclamacion->numero_hoja}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }
}
