<?php

namespace App\Actions\Reclamaciones;

use App\Models\Empresa;
use App\Models\Reclamacion;

/**
 * Caso de uso desacoplado para la consulta de estado de reclamaciones.
 */
class ConsultarEstadoReclamacionAction
{
    /**
     * Consulta el expediente por documento de identidad y número correlativo.
     *
     * @return array<string, mixed>|null
     */
    public function ejecutar(
        Empresa $empresa,
        string $tipoDocumento,
        string $numeroDocumento,
        string $numeroReclamacion
    ): ?array {
        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where('numero_hoja', trim($numeroReclamacion))
            ->where('datos_consumidor->tipo_documento', $tipoDocumento)
            ->where('datos_consumidor->numero_documento', trim($numeroDocumento))
            ->with(['establecimiento', 'respuestas' => fn ($q) => $q->latest()])
            ->first();

        if (! $reclamacion) {
            return null;
        }

        $consumidor = $reclamacion->datos_consumidor ?? [];

        return [
            'id' => $reclamacion->id,
            'numero_hoja' => $reclamacion->numero_hoja,
            'tipo' => $reclamacion->tipo->value,
            'tipo_label' => $reclamacion->tipo->label(),
            'tipo_bien' => $reclamacion->tipo_bien->value,
            'tipo_bien_label' => $reclamacion->tipo_bien->label(),
            'fecha_hora' => $reclamacion->fecha_hora?->format('d/m/Y H:i'),
            'estado' => $reclamacion->estado->value,
            'estado_label' => $reclamacion->estado->label(),
            'consumidor_nombre' => trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? '')),
            'establecimiento' => $reclamacion->establecimiento?->nombre ?? 'Sede Principal',
            'detalle_problema' => $reclamacion->detalle_problema,
            'pedido_consumidor' => $reclamacion->pedido_consumidor,
            'respuesta' => $reclamacion->respuesta_texto,
            'fecha_respuesta' => $reclamacion->fecha_respuesta?->format('d/m/Y H:i'),
            'constancia_pdf_disponible' => (bool) $reclamacion->constancia_pdf_path,
        ];
    }
}
