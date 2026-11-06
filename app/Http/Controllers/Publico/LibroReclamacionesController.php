<?php

namespace App\Http\Controllers\Publico;

use App\Enums\EstadoReclamacion;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Enums\TipoReclamacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\RegistrarReclamacionRequest;
use App\Jobs\EnviarConstanciaReclamacionJob;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionAdjunto;
use App\Models\ReclamacionEvento;
use App\Services\CorrelativoService;
use App\Services\PdfConstanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LibroReclamacionesController extends Controller
{
    /**
     * Versión institucional vigente de la política y aviso de privacidad de datos personales.
     */
    public const AVISO_PRIVACIDAD_VERSION = '1.0';

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

        // Mapear la clasificación formal de reclamaciones (Reclamo vs Queja)
        $tiposReclamacion = array_map(fn (TipoReclamacion $tipo) => [
            'value' => $tipo->value,
            'label' => $tipo->label(),
            'descripcion' => $tipo->descripcion(),
        ], TipoReclamacion::cases());

        // Generación de token firmado/cifrado para validación de tiempo mínimo anti-bot
        $tokenSeguridad = encrypt(now()->timestamp);

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
            'tipos_reclamacion' => $tiposReclamacion,
            'aviso_privacidad_version' => self::AVISO_PRIVACIDAD_VERSION,
            'token_seguridad' => $tokenSeguridad,
        ]);
    }

    /**
     * Procesa y registra la reclamación o queja presentada por el consumidor,
     * generando el correlativo atómico inmutable, almacenando el snapshot original
     * y registrando el evento inicial en el historial de auditoría.
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

        // Ejecución atómica de la generación de correlativo, persistencia de la reclamación y auditoría inicial
        $reclamacion = DB::transaction(function () use ($request, $validated, $empresa, $datosConsumidor, $datosLegalesSnapshot) {
            // Asignación de correlativo con bloqueo pesimista contra duplicidades concurrentes
            $numeroHoja = app(CorrelativoService::class)->generarProximoNumero(
                $empresa,
                $validated['establecimiento_id'] ?? null
            );

            // Creación del registro inmutable de origen vinculado al tenant correspondiente
            $reclamacionCreada = Reclamacion::create([
                'empresa_id' => $empresa->id,
                'establecimiento_id' => $validated['establecimiento_id'] ?? null,
                'numero_hoja' => $numeroHoja,
                'fecha_hora' => now(),
                // Se conserva el tipo seleccionado por el usuario sin reinterpretación automática
                'tipo' => $validated['tipo'],
                'tipo_bien' => $validated['tipo_bien'],
                'monto_reclamado' => $validated['monto_reclamado'] ?? null,
                'moneda' => $validated['moneda'] ?? 'PEN',
                'descripcion_bien' => trim($validated['descripcion_bien']),
                'numero_comprobante' => isset($validated['numero_comprobante']) ? trim($validated['numero_comprobante']) : null,
                'fecha_contratacion' => $validated['fecha_contratacion'] ?? null,
                // Hechos y pretensión concreta expresados directamente por el consumidor
                'detalle_problema' => trim($validated['detalle_problema']),
                'pedido_consumidor' => trim($validated['pedido_consumidor']),
                'datos_consumidor' => $datosConsumidor,
                'datos_legales_snapshot' => $datosLegalesSnapshot,
                // Trazabilidad de lectura y aceptación del aviso legal de protección de datos
                'aviso_privacidad_version' => $validated['aviso_privacidad_version'] ?? self::AVISO_PRIVACIDAD_VERSION,
                'aviso_privacidad_aceptado_en' => now(),
                'aviso_privacidad_ip' => $request->ip(),
                'estado' => EstadoReclamacion::Pendiente,
                'token_consulta' => Str::random(40),
            ]);

            // Registro inmutable del evento inicial en la tabla de auditoría del expediente
            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacionCreada->id,
                'actor_tipo' => 'consumidor',
                'actor_id' => null,
                'actor_nombre' => trim($datosConsumidor['nombres'].' '.($datosConsumidor['apellidos'] ?? '')),
                'accion' => 'presentacion_inicial',
                'descripcion' => "Presentación formal de la {$reclamacionCreada->tipo->label()} con número correlativo {$reclamacionCreada->numero_hoja}.",
                'datos_antes' => null,
                'datos_despues' => [
                    'numero_hoja' => $reclamacionCreada->numero_hoja,
                    'tipo' => $reclamacionCreada->tipo->value,
                    'tipo_bien' => $reclamacionCreada->tipo_bien->value,
                    'consumidor' => $datosConsumidor,
                    'detalle_problema' => $reclamacionCreada->detalle_problema,
                    'pedido_consumidor' => $reclamacionCreada->pedido_consumidor,
                    'proveedor_snapshot' => $datosLegalesSnapshot,
                ],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            // Procesar y almacenar evidencias o adjuntos subidos por el consumidor
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    if ($archivo->isValid()) {
                        $extension = $archivo->getClientOriginalExtension();
                        $nombreAlmacenado = 'adjunto_'.Str::random(32).'.'.$extension;
                        $rutaStorage = $archivo->storeAs("evidencias/{$empresa->id}/{$reclamacionCreada->id}", $nombreAlmacenado, 'local');

                        ReclamacionAdjunto::create([
                            'empresa_id' => $empresa->id,
                            'reclamacion_id' => $reclamacionCreada->id,
                            'nombre_original' => $archivo->getClientOriginalName(),
                            'storage_path' => $rutaStorage,
                            'mime_type' => $archivo->getClientMimeType() ?: $archivo->getMimeType(),
                            'tamano_bytes' => $archivo->getSize(),
                            'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
                            'origen' => 'consumidor',
                        ]);
                    }
                }
            }

            // Despachar tarea asíncrona de envío de constancia por correo electrónico
            EnviarConstanciaReclamacionJob::dispatch($reclamacionCreada);

            return $reclamacionCreada;
        });

        return redirect()->route('libro.confirmacion', [
            'slug' => $empresa->slug,
            'token' => $reclamacion->token_consulta,
        ]);
    }

    /**
     * Muestra la pantalla oficial de confirmación de recepción y constancia con enlace de descarga.
     */
    public function confirmacion(string $slug, string $token): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where('token_consulta', $token)
            ->withCount('adjuntos')
            ->firstOrFail();

        $consumidor = $reclamacion->datos_consumidor ?? [];
        $nombreConsumidor = trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? ''));

        return Inertia::render('Publico/ConstanciaExitosa', [
            'empresa' => [
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'direccion' => $empresa->direccion,
                'email' => $empresa->email,
                'telefono' => $empresa->telefono,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'slug' => $empresa->slug,
            ],
            'reclamacion' => [
                'numero_hoja' => $reclamacion->numero_hoja,
                'tipo' => $reclamacion->tipo->value,
                'tipo_label' => $reclamacion->tipo->label(),
                'tipo_bien_label' => $reclamacion->tipo_bien->label(),
                'fecha_hora' => $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s'),
                'vence_en' => $reclamacion->vence_en?->format('d/m/Y') ?? now()->addWeekdays(15)->format('d/m/Y'),
                'consumidor_nombre' => $nombreConsumidor,
                'consumidor_email' => $consumidor['email'] ?? '',
                'token_consulta' => $reclamacion->token_consulta,
                'adjuntos_count' => $reclamacion->adjuntos_count,
            ],
            'url_descarga_pdf' => route('libro.constancia.pdf', [
                'slug' => $empresa->slug,
                'token' => $reclamacion->token_consulta,
            ]),
        ]);
    }

    /**
     * Genera y descarga el archivo PDF oficial con la constancia de la reclamación.
     */
    public function descargarPdf(string $slug, string $token, PdfConstanciaService $pdfService): HttpResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where('token_consulta', $token)
            ->firstOrFail();

        return $pdfService->descargarPdf($reclamacion);
    }
}
