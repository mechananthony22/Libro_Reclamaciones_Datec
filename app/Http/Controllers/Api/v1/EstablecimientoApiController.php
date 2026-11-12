<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EstablecimientoResource;
use App\Models\Empresa;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API REST v1 para la consulta de sedes y establecimientos de la empresa.
 */
class EstablecimientoApiController extends Controller
{
    /**
     * Lista los establecimientos activos del tenant especificado por slug.
     */
    public function index(string $slug): AnonymousResourceCollection
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::where('slug', $slug)->firstOrFail();

        if (! $empresa->isActiva()) {
            abort(403, 'La empresa especificada no se encuentra activa.');
        }

        $establecimientos = $empresa->establecimientos()
            ->where('activo', true)
            ->orderBy('es_principal', 'desc')
            ->orderBy('nombre', 'asc')
            ->get();

        return EstablecimientoResource::collection($establecimientos);
    }
}
