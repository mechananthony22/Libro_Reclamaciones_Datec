<?php

namespace App\Http\Controllers\Api\v1;

use App\Actions\Reclamaciones\ConsultarEstadoReclamacionAction;
use App\Actions\Reclamaciones\RegistrarReclamacionAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReclamacionResource;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API REST v1 para la recepción de reclamaciones y consulta de expedientes.
 * Diseñado para operar sobre casos de uso desacoplados e independientes de Inertia.
 */
class ReclamacionApiController extends Controller
{
    public function __construct(
        protected RegistrarReclamacionAction $registrarAction,
        protected ConsultarEstadoReclamacionAction $consultarAction
    ) {}

    /**
     * Endpoint REST para registrar una reclamación vía API externa.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::where('slug', $slug)->firstOrFail();
        $empresa->makeCurrent();

        if ($empresa->estaEnContingencia()) {
            return response()->json([
                'error' => 'portal_en_contingencia',
                'mensaje' => $empresa->contingencia_mensaje ?: 'Servicio en contingencia operativa.',
                'canales_alternativos' => $empresa->canalesContingencia(),
            ], 503);
        }

        $validated = $request->validate([
            'tipo' => ['required', 'string', 'in:reclamo,queja'],
            'tipo_bien' => ['required', 'string', 'in:producto,servicio'],
            'establecimiento_id' => ['nullable', 'integer', 'exists:establecimientos,id'],
            'tipo_documento' => ['required', 'string', 'in:dni,ce,pasaporte,ruc,otro'],
            'numero_documento' => ['required', 'string', 'max:20'],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'es_menor_edad' => ['nullable', 'boolean'],
            'apoderado_nombre' => ['nullable', 'string', 'max:200'],
            'apoderado_tipo_documento' => ['nullable', 'string'],
            'apoderado_numero_documento' => ['nullable', 'string', 'max:20'],
            'apoderado_telefono' => ['nullable', 'string', 'max:30'],
            'apoderado_email' => ['nullable', 'email', 'max:150'],
            'monto_reclamado' => ['nullable', 'numeric', 'min:0'],
            'moneda' => ['nullable', 'string', 'in:PEN,USD'],
            'descripcion_bien' => ['required', 'string', 'max:300'],
            'numero_comprobante' => ['nullable', 'string', 'max:100'],
            'fecha_contratacion' => ['nullable', 'date'],
            'detalle_problema' => ['required', 'string', 'max:3000'],
            'pedido_consumidor' => ['required', 'string', 'max:1500'],
            'aviso_privacidad_version' => ['nullable', 'string'],
        ]);

        $reclamacion = $this->registrarAction->ejecutar(
            $empresa,
            $validated,
            $request->file('adjuntos', []),
            $request->ip(),
            $request->userAgent()
        );

        return (new ReclamacionResource($reclamacion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Endpoint REST para consultar el estado de un reclamo por número y documento.
     */
    public function consultar(Request $request, string $slug): JsonResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'tipo_documento' => ['required', 'string'],
            'numero_documento' => ['required', 'string'],
            'numero_reclamacion' => ['required', 'string'],
        ]);

        $resultado = $this->consultarAction->ejecutar(
            $empresa,
            $validated['tipo_documento'],
            $validated['numero_documento'],
            $validated['numero_reclamacion']
        );

        if (! $resultado) {
            return response()->json([
                'error' => 'expediente_no_encontrado',
                'mensaje' => 'No se encontró ninguna reclamación con los datos proporcionados.',
            ], 404);
        }

        return response()->json([
            'data' => $resultado,
        ]);
    }
}
