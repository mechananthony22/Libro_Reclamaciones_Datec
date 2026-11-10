<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso REST para la presentación de establecimientos y sedes corporativas.
 */
class EstablecimientoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'es_principal' => (bool) $this->es_principal,
            'activo' => (bool) $this->activo,
        ];
    }
}
