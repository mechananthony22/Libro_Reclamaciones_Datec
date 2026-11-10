<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso REST para la presentación de expedientes de reclamación en API externa.
 */
class ReclamacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $consumidor = $this->datos_consumidor ?? [];

        return [
            'id' => $this->id,
            'numero_hoja' => $this->numero_hoja,
            'tipo' => $this->tipo?->value ?? (string) $this->tipo,
            'tipo_label' => $this->tipo?->label() ?? '',
            'tipo_bien' => $this->tipo_bien?->value ?? (string) $this->tipo_bien,
            'tipo_bien_label' => $this->tipo_bien?->label() ?? '',
            'monto_reclamado' => $this->monto_reclamado ? (float) $this->monto_reclamado : null,
            'moneda' => $this->moneda ?? 'PEN',
            'descripcion_bien' => $this->descripcion_bien,
            'numero_comprobante' => $this->numero_comprobante,
            'fecha_contratacion' => $this->fecha_contratacion?->toDateString(),
            'detalle_problema' => $this->detalle_problema,
            'pedido_consumidor' => $this->pedido_consumidor,
            'estado' => $this->estado?->value ?? (string) $this->estado,
            'estado_label' => $this->estado?->label() ?? '',
            'fecha_hora' => $this->fecha_hora?->toIso8601String(),
            'consumidor' => [
                'tipo_documento' => $consumidor['tipo_documento'] ?? null,
                'numero_documento' => $consumidor['numero_documento'] ?? null,
                'nombres' => $consumidor['nombres'] ?? null,
                'apellidos' => $consumidor['apellidos'] ?? null,
                'email' => $consumidor['email'] ?? null,
                'telefono' => $consumidor['telefono'] ?? null,
            ],
            'establecimiento' => $this->whenLoaded('establecimiento', fn () => [
                'id' => $this->establecimiento->id,
                'codigo' => $this->establecimiento->codigo,
                'nombre' => $this->establecimiento->nombre,
            ]),
            'token_consulta' => $this->token_consulta,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
