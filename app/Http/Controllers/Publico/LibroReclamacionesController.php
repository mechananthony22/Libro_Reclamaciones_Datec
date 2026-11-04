<?php

namespace App\Http\Controllers\Publico;

use App\Enums\EstadoReclamacion;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\RegistrarReclamacionRequest;
use App\Models\Empresa;
use App\Models\Reclamacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LibroReclamacionesController extends Controller
{
    /**
     * Resuelve y muestra la portada pública oficial del Libro de Reclamaciones
     * y el formulario de ingreso de reclamaciones para la empresa proveedora.
     */
    public function index(string $slug): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        // Validar que la empresa proveedora cuente con la información legal mínima obligatoria antes de publicar
        if (empty($empresa->ruc) || empty($empresa->razon_social) || empty($empresa->direccion)) {
            abort(503, 'El Libro de Reclamaciones de esta empresa se encuentra en proceso de configuración legal obligatoria.');
        }

        // Obtener los establecimientos o sucursales activas registradas por la empresa
        $establecimientos = $empresa->establecimientos()
            ->where('activo', true)
            ->orderBy('es_principal', 'desc')
            ->orderBy('nombre', 'asc')
            ->get(['id', 'codigo', 'nombre', 'direccion', 'es_principal']);

        // Mapear los tipos de documentos de identidad con sus respectivas longitudes y etiquetas
        $tiposDocumento = array_map(fn (TipoDocumentoConsumidor $tipo) => [
            'value' => $tipo->value,
            'label' => $tipo->label(),
            'longitud' => $tipo->longitudExacta(),
        ], TipoDocumentoConsumidor::cases());

        // Mapear los tipos de bien contratado (Producto o Servicio) con sus textos descriptivos
        $tiposBien = array_map(fn (TipoBien $bien) => [
            'value' => $bien->value,
            'label' => $bien->label(),
            'descripcion' => $bien->descripcion(),
        ], TipoBien::cases());

        // Sanitización y entrega de datos públicos del proveedor requeridos por la normativa de Indecopi
        return Inertia::render('Publico/LibroReclamaciones', [
            'empresa' => [
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'direccion' => $empresa->direccion,
                'email' => $empresa->email,
                'telefono' => $empresa->telefono,
                'logo_path' => $empresa->logo_path,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'slug' => $empresa->slug,
            ],
            'establecimientos' => $establecimientos,
            'tipos_documento' => $tiposDocumento,
            'tipos_bien' => $tiposBien,
        ]);
    }

    /**
     * Procesa y registra la reclamación o queja presentada por el consumidor,
     * almacenando la evidencia, los datos del consumidor y el snapshot del proveedor.
     */
    public function registrar(RegistrarReclamacionRequest $request, string $slug): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $validated = $request->validated();

        // Estructurar los datos de identificación, domicilio y contacto del consumidor reclamante
        $datosConsumidor = [
            'tipo_documento' => $validated['tipo_documento'],
            'numero_documento' => trim($validated['numero_documento']),
            'nombres' => trim($validated['nombres']),
            'apellidos' => isset($validated['apellidos']) ? trim($validated['apellidos']) : null,
            'email' => trim(strtolower($validated['email'])),
            'telefono' => trim($validated['telefono']),
            'direccion' => trim($validated['direccion']),
            'departamento' => $validated['departamento'] ?? null,
            'provincia' => $validated['provincia'] ?? null,
            'distrito' => $validated['distrito'] ?? null,
            'es_menor_edad' => (bool) $validated['es_menor_edad'],
        ];

        // En caso de que el consumidor sea menor de edad, registrar los datos obligatorios del representante legal
        if ($datosConsumidor['es_menor_edad']) {
            $datosConsumidor['apoderado'] = [
                'nombre' => trim($validated['apoderado_nombre'] ?? ''),
                'tipo_documento' => $validated['apoderado_tipo_documento'] ?? null,
                'numero_documento' => trim($validated['apoderado_numero_documento'] ?? ''),
                'telefono' => trim($validated['apoderado_telefono'] ?? ''),
                'email' => trim(strtolower($validated['apoderado_email'] ?? '')),
            ];
        }

        // Generar una instantánea inmutable con los datos legales del proveedor al momento del registro
        $datosLegalesSnapshot = [
            'razon_social' => $empresa->razon_social,
            'ruc' => $empresa->ruc,
            'direccion' => $empresa->direccion,
            'email' => $empresa->email,
            'telefono' => $empresa->telefono,
            'capturado_en' => now()->toIso8601String(),
        ];

        // Crear la reclamación en la base de datos vinculada de forma estricta al tenant correspondiente
        Reclamacion::create([
            'empresa_id' => $empresa->id,
            'establecimiento_id' => $validated['establecimiento_id'] ?? null,
            'fecha_hora' => now(),
            'tipo' => 'reclamo',
            'tipo_bien' => $validated['tipo_bien'],
            'monto_reclamado' => $validated['monto_reclamado'] ?? null,
            'moneda' => $validated['moneda'] ?? 'PEN',
            'descripcion_bien' => trim($validated['descripcion_bien']),
            'numero_comprobante' => isset($validated['numero_comprobante']) ? trim($validated['numero_comprobante']) : null,
            'fecha_contratacion' => $validated['fecha_contratacion'] ?? null,
            'datos_consumidor' => $datosConsumidor,
            'datos_legales_snapshot' => $datosLegalesSnapshot,
            'estado' => EstadoReclamacion::Pendiente,
            'token_consulta' => Str::random(40),
        ]);

        return redirect()->route('libro.publico', ['slug' => $empresa->slug])
            ->with('success', 'Los datos del consumidor y la identificación del bien o servicio han sido registrados exitosamente.');
    }
}
