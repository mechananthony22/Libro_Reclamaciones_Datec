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

        $pdf = Pdf::loadView('pdf.constancia_reclamacion', [
            'reclamacion' => $reclamacion,
            'empresa' => $empresa,
            'consumidor' => $consumidor,
            'proveedor' => $proveedorSnapshot,
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
