<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\DecisionRespuesta;
use App\Enums\EstadoReclamacion;
use App\Enums\TipoReclamacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\AsignarResponsableRequest;
use App\Http\Requests\Empresa\CambiarEstadoReclamacionRequest;
use App\Http\Requests\Empresa\RegistrarRespuestaRequest;
use App\Jobs\EnviarRespuestaReclamacionJob;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Models\ReclamacionAdjunto;
use App\Models\ReclamacionEvento;
use App\Models\ReclamacionRespuesta;
use App\Services\AuditoriaService;
use App\Services\PdfConstanciaService;
use App\Services\PlazoReclamacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de bandeja de expedientes y visualización detallada de la ficha de reclamación.
 * Garantiza el aislamiento multitenant estricto y la inmutabilidad de los datos originales del consumidor.
 */
class ExpedienteController extends Controller
{
    public function __construct(
        protected PlazoReclamacionService $plazoService,
        protected PdfConstanciaService $pdfService
    ) {}

    /**
     * Construye la consulta base de reclamaciones aplicando los filtros solicitados para el tenant actual.
     *
     * @return Builder<Reclamacion>
     */
    protected function construirConsultaFiltrada(Request $request, int $empresaId)
    {
        $query = Reclamacion::where('empresa_id', $empresaId)
            ->with(['establecimiento:id,codigo,nombre', 'responsable:id,name,email']);

        // Filtro por término de búsqueda (número de hoja, comprobante, nombres, apellidos o documento del consumidor)
        if ($buscar = trim((string) $request->input('buscar', ''))) {
            $esPgsql = config('database.default') === 'pgsql';
            $likeOperador = $esPgsql ? 'ilike' : 'like';
            $termino = strtolower($buscar);

            // Al estar datos_consumidor cifrado en reposo (AES-256), se evalúa la coincidencia del consumidor en memoria para el tenant
            $matchingIds = Reclamacion::where('empresa_id', $empresaId)
                ->get(['id', 'datos_consumidor'])
                ->filter(function ($rec) use ($termino) {
                    $c = $rec->datos_consumidor ?? [];
                    $nombres = strtolower((string) ($c['nombres'] ?? ''));
                    $apellidos = strtolower((string) ($c['apellidos'] ?? ''));
                    $numDoc = strtolower((string) ($c['numero_documento'] ?? ''));
                    $email = strtolower((string) ($c['email'] ?? ''));

                    return str_contains($nombres, $termino)
                        || str_contains($apellidos, $termino)
                        || str_contains($numDoc, $termino)
                        || str_contains($email, $termino);
                })
                ->pluck('id');

            $query->where(function ($q) use ($buscar, $likeOperador, $matchingIds) {
                $q->where('numero_hoja', $likeOperador, "%{$buscar}%")
                    ->orWhere('numero_comprobante', $likeOperador, "%{$buscar}%")
                    ->orWhereIn('id', $matchingIds);
            });
        }

        // Filtro por estado formal
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        // Filtro por clasificación de caso (reclamo / queja)
        if ($tipo = $request->input('tipo')) {
            $query->where('tipo', $tipo);
        }

        // Filtro por establecimiento / sucursal
        if ($establecimientoId = $request->input('establecimiento_id')) {
            $query->where('establecimiento_id', $establecimientoId);
        }

        // Filtro por rango de fechas de presentación
        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $fechaDesde);
        }
        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $fechaHasta);
        }

        return $query;
    }

    /**
     * Valida la regla de acceso controlado a contenido privado de expedientes.
     * Permite acceso directo a miembros activos de la empresa y a Superadministradores de la plataforma.
     */
    protected function validarAccesoContenido(Request $request, Empresa $empresa): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $esMiembroDirecto = $user->empresas()
            ->where('tenants.id', $empresa->id)
            ->wherePivot('activo', true)
            ->exists();

        if ($esMiembroDirecto) {
            return;
        }

        // El Superadministrador de Datec cuenta con privilegios globales de supervisión y gestión técnica
        if ($user->isSuperAdmin()) {
            $autorizacion = $empresa->autorizacionSoporteActiva();

            if ($autorizacion) {
                // Registrar el acceso delegado formal de soporte en la bitácora de auditoría
                app(AuditoriaService::class)->registrar(
                    'acceso_delegado_soporte',
                    'soporte',
                    "Acceso delegado de soporte técnico a expedientes de {$empresa->name} (Ticket {$autorizacion->codigo_ticket}) por Superadmin {$user->name}",
                    [
                        'empresa_id' => $empresa->id,
                        'user' => $user,
                        'datos_despues' => [
                            'codigo_ticket' => $autorizacion->codigo_ticket,
                            'valido_hasta' => $autorizacion->valido_hasta?->toIso8601String(),
                        ],
                    ]
                );
            } else {
                // Registrar acceso de supervisión global de plataforma por Superadmin
                app(AuditoriaService::class)->registrar(
                    'acceso_superadmin_expedientes',
                    'expedientes',
                    "Superadmin {$user->name} consultó expedientes de {$empresa->name} con privilegios globales de plataforma.",
                    [
                        'empresa_id' => $empresa->id,
                        'user' => $user,
                    ]
                );
            }

            return;
        }

        abort(403, 'Acceso denegado: no cuentas con permisos para consultar los expedientes de esta empresa.');
    }

    /**
     * Valida los permisos de modificación sobre los expedientes del tenant.
     * Los miembros activos de la empresa pueden modificar libremente.
     * El Superadministrador puede visualizar en modo supervisión, pero solo puede
     * modificar, responder o reasignar si cuenta con una autorización de soporte técnico activa otorgada por la empresa.
     */
    protected function validarAccesoModificacion(Request $request, Empresa $empresa): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $esMiembroDirecto = $user->empresas()
            ->where('tenants.id', $empresa->id)
            ->wherePivot('activo', true)
            ->exists();

        if ($esMiembroDirecto) {
            return;
        }

        // Si es Superadmin, verificar si cuenta con ticket de soporte técnico activo
        if ($user->isSuperAdmin()) {
            $autorizacion = $empresa->autorizacionSoporteActiva();

            if ($autorizacion) {
                return;
            }

            abort(403, 'Acceso de solo lectura: el superadministrador puede supervisar expedientes, pero para modificarlos o emitir respuestas se requiere una autorización de soporte técnico activa otorgada por la empresa.');
        }

        abort(403, 'Acceso denegado: no cuentas con permisos para modificar expedientes de esta empresa.');
    }

    /**
     * Muestra la bandeja de expedientes con filtros por estado, tipo, sede, rango de fechas y búsqueda.
     */
    public function index(Request $request): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $query = $this->construirConsultaFiltrada($request, $empresa->id);

        // Métricas de conteo para pestañas y tarjetas informativas
        $metricas = [
            'total' => Reclamacion::where('empresa_id', $empresa->id)->count(),
            'pendientes' => Reclamacion::where('empresa_id', $empresa->id)->where('estado', EstadoReclamacion::Pendiente->value)->count(),
            'en_tramite' => Reclamacion::where('empresa_id', $empresa->id)->where('estado', EstadoReclamacion::EnTramite->value)->count(),
            'atendidas' => Reclamacion::where('empresa_id', $empresa->id)->where('estado', EstadoReclamacion::Atendida->value)->count(),
            'cerradas' => Reclamacion::where('empresa_id', $empresa->id)->where('estado', EstadoReclamacion::Cerrada->value)->count(),
        ];

        $expedientesPaginados = $query->orderBy('fecha_hora', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Transformación con cálculo en tiempo real del semáforo legal
        $expedientesTransformados = $expedientesPaginados->through(function (Reclamacion $rec) {
            $semaforo = $rec->vence_en
                ? $this->plazoService->obtenerEstadoSemaforo($rec->vence_en, $rec->estado->value)
                : null;

            $consumidor = $rec->datos_consumidor ?? [];

            return [
                'id' => $rec->id,
                'numero_hoja' => $rec->numero_hoja,
                'fecha_hora' => $rec->fecha_hora?->format('d/m/Y H:i') ?? '',
                'tipo' => $rec->tipo->value,
                'tipo_label' => $rec->tipo->label(),
                'tipo_bien' => $rec->tipo_bien->label(),
                'descripcion_bien' => $rec->descripcion_bien,
                'monto_reclamado' => $rec->monto_reclamado ? "{$rec->moneda} {$rec->monto_reclamado}" : null,
                'estado' => $rec->estado->value,
                'estado_label' => $rec->estado->label(),
                'consumidor_nombre' => trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? '')),
                'consumidor_documento' => strtoupper($consumidor['tipo_documento'] ?? 'DNI').': '.($consumidor['numero_documento'] ?? ''),
                'establecimiento' => $rec->establecimiento ? [
                    'id' => $rec->establecimiento->id,
                    'nombre' => $rec->establecimiento->nombre,
                ] : null,
                'responsable' => $rec->responsable ? [
                    'id' => $rec->responsable->id,
                    'name' => $rec->responsable->name,
                ] : null,
                'vence_en' => $rec->vence_en?->format('d/m/Y'),
                'semaforo' => $semaforo,
            ];
        });

        $establecimientos = Establecimiento::where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre']);

        return Inertia::render('Empresa/Expedientes/Index', [
            'expedientes' => $expedientesTransformados,
            'filtros' => [
                'buscar' => $request->input('buscar', ''),
                'estado' => $request->input('estado', ''),
                'tipo' => $request->input('tipo', ''),
                'establecimiento_id' => $request->input('establecimiento_id', ''),
                'fecha_desde' => $request->input('fecha_desde', ''),
                'fecha_hasta' => $request->input('fecha_hasta', ''),
            ],
            'metricas' => $metricas,
            'establecimientos' => $establecimientos,
            'estados' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], EstadoReclamacion::cases()),
            'tipos' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], TipoReclamacion::cases()),
        ]);
    }

    /**
     * Exporta los expedientes filtrados en formato CSV o PDF garantizando el aislamiento
     * del tenant y auditando de manera inmutable la acción de consulta y extracción.
     */
    public function exportar(Request $request)
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $user = $request->user();
        $formato = strtolower((string) $request->input('formato', 'csv'));

        $query = $this->construirConsultaFiltrada($request, $empresa->id)
            ->with(['establecimiento', 'responsable', 'respuestas'])
            ->orderBy('fecha_hora', 'desc');

        $totalRegistros = (clone $query)->count();
        $timestamp = now('America/Lima')->format('Ymd_His');
        $filtrosAplicados = $request->only(['buscar', 'estado', 'tipo', 'establecimiento_id', 'fecha_desde', 'fecha_hasta']);

        // Registrar auditoría general del reporte exportado en la plataforma
        app(AuditoriaService::class)->registrar(
            'expedientes_exportados',
            'expedientes',
            'Exportación de expedientes en formato '.strtoupper($formato)." (Total: {$totalRegistros} registros)",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'formato' => $formato,
                    'total_registros' => $totalRegistros,
                    'filtros' => $filtrosAplicados,
                ],
            ]
        );

        if ($formato === 'pdf') {
            // Para PDF se aplica un tope seguro para evitar desbordamiento de memoria en DomPDF
            $limitePdf = 500;
            $reclamaciones = $query->limit($limitePdf)->get();

            if ($reclamaciones->isNotEmpty()) {
                $eventos = [];
                $ahora = now();
                $formatoUpper = strtoupper($formato);
                foreach ($reclamaciones as $rec) {
                    $eventos[] = [
                        'empresa_id' => $empresa->id,
                        'reclamacion_id' => $rec->id,
                        'actor_tipo' => 'usuario',
                        'actor_id' => $user?->id,
                        'actor_nombre' => $user?->name ?? 'Usuario interno',
                        'accion' => 'expediente_exportado',
                        'descripcion' => "Expediente incluido en reporte exportado ({$formatoUpper}) por ".($user?->name ?? 'Usuario'),
                        'datos_despues' => json_encode([
                            'formato' => $formato,
                            'filtros' => $filtrosAplicados,
                        ]),
                        'ip' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 500),
                        'created_at' => $ahora,
                    ];
                }
                foreach (array_chunk($eventos, 200) as $batch) {
                    ReclamacionEvento::insert($batch);
                }
            }

            $expedientesTransformados = $reclamaciones->map(function (Reclamacion $rec) {
                $consumidor = $rec->datos_consumidor ?? [];
                $semaforo = $rec->vence_en
                    ? $this->plazoService->obtenerEstadoSemaforo($rec->vence_en, $rec->estado->value)
                    : null;

                return [
                    'numero_hoja' => $rec->numero_hoja,
                    'fecha_hora' => $rec->fecha_hora?->format('d/m/Y H:i') ?? '',
                    'tipo' => $rec->tipo->value,
                    'tipo_label' => $rec->tipo->label(),
                    'tipo_bien' => $rec->tipo_bien->label(),
                    'descripcion_bien' => $rec->descripcion_bien,
                    'monto_reclamado' => $rec->monto_reclamado ? "{$rec->moneda} {$rec->monto_reclamado}" : null,
                    'estado' => $rec->estado->value,
                    'estado_label' => $rec->estado->label(),
                    'consumidor_nombre' => trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? '')),
                    'consumidor_documento' => strtoupper($consumidor['tipo_documento'] ?? 'DNI').': '.($consumidor['numero_documento'] ?? ''),
                    'consumidor_email' => $consumidor['email'] ?? '',
                    'establecimiento_nombre' => $rec->establecimiento?->nombre ?? 'Sede Principal / Virtual',
                    'responsable_nombre' => $rec->responsable?->name,
                    'vence_en' => $rec->vence_en?->format('d/m/Y'),
                    'semaforo' => $semaforo,
                ];
            });

            $pdf = Pdf::loadView('pdf.reporte_reclamaciones', [
                'empresa' => $empresa,
                'expedientes' => $expedientesTransformados,
                'filtros' => $filtrosAplicados,
                'generadoEn' => now('America/Lima')->format('d/m/Y H:i:s'),
                'usuarioNombre' => $user?->name ?? 'Usuario',
            ]);
            $pdf->setPaper('a4', 'landscape');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"reporte_reclamaciones_{$empresa->slug}_{$timestamp}.pdf\"",
            ]);
        }

        // Exportación en formato CSV mediante streaming perezoso (cursor) para no sobrecargar memoria
        $nombreArchivo = "reclamaciones_{$empresa->slug}_{$timestamp}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query, $empresa, $user, $formato, $filtrosAplicados, $request) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados de columnas
            fputcsv($handle, [
                'N° Hoja',
                'Fecha y Hora',
                'Tipo',
                'Estado',
                'Sede / Establecimiento',
                'Consumidor Nombres',
                'Consumidor Apellidos',
                'Consumidor Tipo Doc',
                'Consumidor N° Doc',
                'Consumidor Teléfono',
                'Consumidor Email',
                'Consumidor Domicilio',
                'Menor de Edad',
                'Apoderado Nombres',
                'Apoderado N° Doc',
                'Tipo de Bien',
                'Descripción del Bien',
                'Moneda',
                'Monto Reclamado',
                'N° Comprobante',
                'Detalle Reclamación / Queja',
                'Pedido del Consumidor',
                'Fecha Vencimiento Legal',
                'Días Hábiles Restantes',
                'Estado Semáforo',
                'Responsable Asignado',
                'Respuesta Formal',
                'Fecha de Respuesta',
            ]);

            $eventosBatch = [];
            $ahora = now();
            $formatoUpper = strtoupper($formato);
            $ip = $request->ip();
            $userAgent = substr((string) $request->userAgent(), 0, 500);

            // Transmisión con cursor(): lee registro por registro sin hidratar colecciones completas en RAM
            foreach ($query->cursor() as $rec) {
                $consumidor = $rec->datos_consumidor ?? [];
                $apoderado = $consumidor['apoderado'] ?? null;
                $semaforo = $rec->vence_en
                    ? $this->plazoService->obtenerEstadoSemaforo($rec->vence_en, $rec->estado->value)
                    : null;
                $ultimaRespuesta = $rec->respuestas->sortByDesc('fecha_respuesta')->first() ?? $rec->respuestas->sortByDesc('id')->first();

                fputcsv($handle, [
                    $rec->numero_hoja,
                    $rec->fecha_hora?->format('d/m/Y H:i:s') ?? '',
                    $rec->tipo->label(),
                    $rec->estado->label(),
                    $rec->establecimiento?->nombre ?? 'Sede Principal',
                    $consumidor['nombres'] ?? '',
                    $consumidor['apellidos'] ?? '',
                    strtoupper($consumidor['tipo_documento'] ?? ''),
                    $consumidor['numero_documento'] ?? '',
                    $consumidor['telefono'] ?? '',
                    $consumidor['email'] ?? '',
                    $consumidor['direccion'] ?? '',
                    ! empty($consumidor['es_menor_edad']) ? 'SÍ' : 'NO',
                    $apoderado['nombre'] ?? '',
                    $apoderado['numero_documento'] ?? '',
                    $rec->tipo_bien->label(),
                    $rec->descripcion_bien,
                    $rec->moneda ?? '',
                    $rec->monto_reclamado ?? '',
                    $rec->numero_comprobante ?? '',
                    $rec->detalle_problema ?? '',
                    $rec->pedido_consumidor ?? '',
                    $rec->vence_en?->format('d/m/Y') ?? '',
                    $semaforo['dias_restantes'] ?? '',
                    $semaforo['etiqueta'] ?? '',
                    $rec->responsable?->name ?? 'Sin asignar',
                    $ultimaRespuesta?->propuesta_solucion ?? '',
                    $ultimaRespuesta?->fecha_respuesta?->format('d/m/Y H:i') ?? $ultimaRespuesta?->created_at?->format('d/m/Y H:i') ?? '',
                ]);

                // Registrar auditoría por expediente en lotes seguros de 200
                $eventosBatch[] = [
                    'empresa_id' => $empresa->id,
                    'reclamacion_id' => $rec->id,
                    'actor_tipo' => 'usuario',
                    'actor_id' => $user?->id,
                    'actor_nombre' => $user?->name ?? 'Usuario interno',
                    'accion' => 'expediente_exportado',
                    'descripcion' => "Expediente incluido en reporte exportado ({$formatoUpper}) por ".($user?->name ?? 'Usuario'),
                    'datos_despues' => json_encode([
                        'formato' => $formato,
                        'filtros' => $filtrosAplicados,
                    ]),
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'created_at' => $ahora,
                ];

                if (count($eventosBatch) >= 200) {
                    ReclamacionEvento::insert($eventosBatch);
                    $eventosBatch = [];
                }
            }

            if (! empty($eventosBatch)) {
                ReclamacionEvento::insert($eventosBatch);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Muestra la ficha íntegra de la reclamación con datos inmutables del consumidor,
     * actuaciones, plazos calculados, evidencias adjuntas e historial de eventos.
     */
    public function show(Request $request, int $id): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->with([
                'establecimiento',
                'responsable:id,name,email',
                'adjuntos',
                'respuestas.autor:id,name,email',
                'eventos' => fn ($q) => $q->orderBy('created_at', 'desc'),
                'envios' => fn ($q) => $q->orderBy('created_at', 'desc'),
            ])
            ->findOrFail($id);

        $semaforo = $reclamacion->vence_en
            ? $this->plazoService->obtenerEstadoSemaforo($reclamacion->vence_en, $reclamacion->estado->value)
            : null;

        // Lista de usuarios asignables de la misma empresa
        $usuariosEmpresa = $empresa->usuarios()
            ->wherePivot('activo', true)
            ->get(['users.id', 'users.name', 'users.email']);

        return Inertia::render('Empresa/Expedientes/Show', [
            'expediente' => [
                'id' => $reclamacion->id,
                'numero_hoja' => $reclamacion->numero_hoja,
                'fecha_hora' => $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? '',
                'tipo' => $reclamacion->tipo->value,
                'tipo_label' => $reclamacion->tipo->label(),
                'tipo_bien' => $reclamacion->tipo_bien->value,
                'tipo_bien_label' => $reclamacion->tipo_bien->label(),
                'monto_reclamado' => $reclamacion->monto_reclamado,
                'moneda' => $reclamacion->moneda,
                'descripcion_bien' => $reclamacion->descripcion_bien,
                'numero_comprobante' => $reclamacion->numero_comprobante,
                'fecha_contratacion' => $reclamacion->fecha_contratacion?->format('d/m/Y'),
                'detalle_problema' => $reclamacion->detalle_problema,
                'pedido_consumidor' => $reclamacion->pedido_consumidor,
                'datos_consumidor' => $reclamacion->datos_consumidor,
                'datos_legales_snapshot' => $reclamacion->datos_legales_snapshot,
                'estado' => $reclamacion->estado->value,
                'estado_label' => $reclamacion->estado->label(),
                'vence_en' => $reclamacion->vence_en?->format('d/m/Y'),
                'token_consulta' => $reclamacion->token_consulta,
                'aviso_privacidad_version' => $reclamacion->aviso_privacidad_version,
                'aviso_privacidad_aceptado_en' => $reclamacion->aviso_privacidad_aceptado_en?->format('d/m/Y H:i:s'),
                'aviso_privacidad_ip' => $reclamacion->aviso_privacidad_ip,
                'establecimiento' => $reclamacion->establecimiento ? [
                    'id' => $reclamacion->establecimiento->id,
                    'codigo' => $reclamacion->establecimiento->codigo,
                    'nombre' => $reclamacion->establecimiento->nombre,
                    'direccion' => $reclamacion->establecimiento->direccion,
                ] : null,
                'responsable' => $reclamacion->responsable ? [
                    'id' => $reclamacion->responsable->id,
                    'name' => $reclamacion->responsable->name,
                    'email' => $reclamacion->responsable->email,
                ] : null,
                'asignado_en' => $reclamacion->asignado_en?->format('d/m/Y H:i'),
                'semaforo' => $semaforo,
                'adjuntos' => $reclamacion->adjuntos->map(fn (ReclamacionAdjunto $adj) => [
                    'id' => $adj->id,
                    'nombre_original' => $adj->nombre_original,
                    'mime_type' => $adj->mime_type,
                    'tamano_kb' => round($adj->tamano_bytes / 1024, 1),
                    'created_at' => $adj->created_at?->format('d/m/Y H:i'),
                    'url_descarga' => route('empresa.expedientes.adjunto.descargar', ['id' => $reclamacion->id, 'adjuntoId' => $adj->id]),
                ]),
                'eventos' => $reclamacion->eventos->map(fn ($ev) => [
                    'id' => $ev->id,
                    'accion' => $ev->accion,
                    'descripcion' => $ev->descripcion,
                    'actor_tipo' => $ev->actor_tipo,
                    'actor_nombre' => $ev->actor_nombre,
                    'created_at' => $ev->created_at?->format('d/m/Y H:i:s'),
                    'ip' => $ev->ip,
                ]),
                'envios' => $reclamacion->envios->map(fn ($env) => [
                    'id' => $env->id,
                    'tipo' => $env->tipo,
                    'canal' => $env->canal,
                    'destinatario' => $env->destinatario,
                    'estado' => $env->estado,
                    'enviado_at' => $env->enviado_en?->format('d/m/Y H:i:s'),
                    'intentado_at' => $env->created_at?->format('d/m/Y H:i:s'),
                    'intentos' => $env->intentos,
                    'mensaje_error' => $env->error_mensaje,
                ]),
                'respuestas' => $reclamacion->respuestas->map(fn (ReclamacionRespuesta $resp) => [
                    'id' => $resp->id,
                    'decision' => $resp->decision->value,
                    'decision_label' => $resp->decision->label(),
                    'propuesta_solucion' => $resp->propuesta_solucion,
                    'fundamento_legal' => $resp->fundamento_legal,
                    'autor_nombre' => $resp->autor_nombre,
                    'autor_cargo' => $resp->autor_cargo,
                    'version' => $resp->version,
                    'fecha_respuesta' => $resp->fecha_respuesta?->format('d/m/Y H:i'),
                    'notificado' => $resp->notificado,
                    'notificado_en' => $resp->notificado_en?->format('d/m/Y H:i'),
                    'adjuntos' => collect($resp->adjuntos_paths ?? [])->map(function ($adj, int $idx) use ($reclamacion, $resp) {
                        $nombre = is_array($adj) ? ($adj['nombre'] ?? basename($adj['path'] ?? 'adjunto')) : basename($adj);
                        $tamanoBytes = is_array($adj) ? ($adj['tamano_bytes'] ?? null) : null;
                        $mimeType = is_array($adj) ? ($adj['mime_type'] ?? null) : null;

                        return [
                            'indice' => $idx,
                            'nombre' => $nombre,
                            'tamano_kb' => $tamanoBytes ? round($tamanoBytes / 1024, 1) : null,
                            'mime_type' => $mimeType,
                            'url_descarga' => route('empresa.expedientes.respuestas.adjunto.descargar', [
                                'id' => $reclamacion->id,
                                'respuestaId' => $resp->id,
                                'indice' => $idx,
                            ]),
                        ];
                    })->values()->all(),
                ]),
            ],
            'usuarios_asignables' => $usuariosEmpresa,
            'decisiones_disponibles' => array_map(fn (DecisionRespuesta $d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ], DecisionRespuesta::cases()),
            'estados_disponibles' => array_map(function (EstadoReclamacion $case) use ($reclamacion) {
                $permitido = $reclamacion->estado->puedeTransitarHacia($case);
                if (in_array($case, [EstadoReclamacion::Atendida, EstadoReclamacion::Cerrada], true) && $reclamacion->respuestas->isEmpty()) {
                    $permitido = false;
                }

                return [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'es_actual' => $case === $reclamacion->estado,
                    'permitido' => $permitido,
                ];
            }, EstadoReclamacion::cases()),
        ]);
    }

    /**
     * Descarga la constancia oficial en formato PDF para el expediente consultado.
     */
    public function descargarPdf(Request $request, int $id)
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);

        return $this->pdfService->descargarPdf($reclamacion);
    }

    /**
     * Descarga segura de un archivo o evidencia adjunta previa validación de pertenencia al tenant.
     */
    public function descargarAdjunto(Request $request, int $id, int $adjuntoId)
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);

        $adjunto = ReclamacionAdjunto::where('empresa_id', $empresa->id)
            ->where('reclamacion_id', $reclamacion->id)
            ->findOrFail($adjuntoId);

        if (! Storage::disk('local')->exists($adjunto->storage_path)) {
            abort(404, 'El archivo solicitado no se encuentra en el repositorio.');
        }

        return Storage::disk('local')->download($adjunto->storage_path, $adjunto->nombre_original);
    }

    /**
     * Descarga segura de un archivo adjunto a la respuesta formal previa validación de pertenencia al tenant.
     */
    public function descargarAdjuntoRespuesta(Request $request, int $id, int $respuestaId, int $indice)
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoContenido($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);
        $respuesta = ReclamacionRespuesta::where('empresa_id', $empresa->id)
            ->where('reclamacion_id', $reclamacion->id)
            ->findOrFail($respuestaId);

        $adjuntos = $respuesta->adjuntos_paths ?? [];
        if (! isset($adjuntos[$indice])) {
            abort(404, 'El archivo adjunto solicitado no existe.');
        }

        $adj = $adjuntos[$indice];
        $path = is_array($adj) ? ($adj['path'] ?? '') : $adj;
        $nombre = is_array($adj) ? ($adj['nombre'] ?? basename($path)) : basename($path);

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'El archivo solicitado no se encuentra en el repositorio.');
        }

        return Storage::disk('local')->download($path, $nombre);
    }

    /**
     * Asigna o reasigna un usuario responsable dentro de la misma empresa para gestionar el expediente.
     * Registra auditoría inmutable en reclamacion_eventos impidiendo asignaciones externas.
     */
    public function asignarResponsable(AsignarResponsableRequest $request, int $id): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoModificacion($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);

        $usuarioId = $request->validated('usuario_id');
        $motivo = trim((string) $request->validated('motivo', ''));

        $usuarioNuevo = null;
        if ($usuarioId) {
            // Validar de forma estricta que el usuario pertenezca y esté activo en esta empresa
            $usuarioNuevo = $empresa->usuarios()
                ->where('users.id', $usuarioId)
                ->wherePivot('activo', true)
                ->first();

            if (! $usuarioNuevo) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El usuario seleccionado no pertenece a esta empresa o se encuentra inactivo.',
                ]);
            }
        }

        $responsableAnterior = $reclamacion->responsable;

        DB::transaction(function () use ($reclamacion, $empresa, $usuarioNuevo, $responsableAnterior, $motivo, $request) {
            $reclamacion->update([
                'asignado_a' => $usuarioNuevo?->id,
                'asignado_en' => $usuarioNuevo ? now() : null,
            ]);

            // Descripción detallada para la auditoría inmutable
            if ($usuarioNuevo) {
                $descripcion = $responsableAnterior
                    ? "Expediente reasignado de {$responsableAnterior->name} a {$usuarioNuevo->name}."
                    : "Expediente asignado al responsable {$usuarioNuevo->name}.";
            } else {
                $descripcion = "Expediente desasignado (responsable anterior: {$responsableAnterior?->name}).";
            }

            if ($motivo !== '') {
                $descripcion .= " Nota: {$motivo}";
            }

            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacion->id,
                'actor_tipo' => 'usuario',
                'actor_id' => $request->user()?->id,
                'actor_nombre' => $request->user()?->name ?? 'Usuario interno',
                'accion' => $usuarioNuevo ? 'asignacion_responsable' : 'desasignacion_responsable',
                'descripcion' => $descripcion,
                'datos_antes' => ['asignado_a' => $responsableAnterior?->id, 'nombre' => $responsableAnterior?->name],
                'datos_despues' => ['asignado_a' => $usuarioNuevo?->id, 'nombre' => $usuarioNuevo?->name],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        $mensaje = $usuarioNuevo
            ? "Expediente asignado exitosamente a {$usuarioNuevo->name}."
            : 'Se ha retirado la asignación del responsable en este expediente.';

        return back()->with('success', $mensaje);
    }

    /**
     * Gestiona las transiciones controladas de estado del expediente (Pendiente -> En trámite -> Atendida -> Cerrada).
     * Conserva el histórico completo sin alterar la fecha de ingreso original ni las evidencias.
     */
    public function cambiarEstado(CambiarEstadoReclamacionRequest $request, int $id): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoModificacion($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);

        $nuevoEstado = EstadoReclamacion::from($request->validated('estado'));
        $estadoActual = $reclamacion->estado;
        $motivo = trim((string) $request->validated('motivo'));

        // Si ya tiene ese estado no realizar transición
        if ($estadoActual === $nuevoEstado) {
            return back()->with('info', "El expediente ya se encuentra en estado {$nuevoEstado->label()}.");
        }

        // Validar que la transición sea permitida por la máquina de estados
        if (! $estadoActual->puedeTransitarHacia($nuevoEstado)) {
            throw ValidationException::withMessages([
                'estado' => "No está permitida la transición directa desde '{$estadoActual->label()}' hacia '{$nuevoEstado->label()}'.",
            ]);
        }

        // Si se intenta pasar a Atendida o Cerrada, exigir que exista una respuesta formal registrada conforme a ley
        if (in_array($nuevoEstado, [EstadoReclamacion::Atendida, EstadoReclamacion::Cerrada], true)) {
            if (! $reclamacion->respuestas()->exists()) {
                throw ValidationException::withMessages([
                    'estado' => "No es posible cambiar el estado a '{$nuevoEstado->label()}' sin haber registrado una respuesta formal al consumidor. Por favor registre la respuesta formal en la sección correspondiente.",
                ]);
            }
        }

        DB::transaction(function () use ($reclamacion, $empresa, $estadoActual, $nuevoEstado, $motivo, $request) {
            $reclamacion->update([
                'estado' => $nuevoEstado,
            ]);

            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacion->id,
                'actor_tipo' => 'usuario',
                'actor_id' => $request->user()?->id,
                'actor_nombre' => $request->user()?->name ?? 'Usuario interno',
                'accion' => 'cambio_estado',
                'descripcion' => "Cambio de estado: '{$estadoActual->label()}' → '{$nuevoEstado->label()}'. Motivo: {$motivo}",
                'datos_antes' => ['estado' => $estadoActual->value, 'label' => $estadoActual->label()],
                'datos_despues' => ['estado' => $nuevoEstado->value, 'label' => $nuevoEstado->label()],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        app(AuditoriaService::class)->registrar(
            'expediente_estado_cambiado',
            'expedientes',
            "Expediente {$reclamacion->numero_hoja}: cambio de estado '{$estadoActual->label()}' → '{$nuevoEstado->label()}'",
            [
                'empresa_id' => $empresa->id,
                'datos_antes' => ['estado' => $estadoActual->value],
                'datos_despues' => ['estado' => $nuevoEstado->value, 'motivo' => $motivo],
            ]
        );

        return back()->with('success', "Estado actualizado correctamente a '{$nuevoEstado->label()}'.");
    }

    /**
     * Registra formalmente la respuesta escrita del proveedor frente al reclamo o queja.
     * Asocia la decisión, fundamentación, archivos probatorios y autoría del responsable.
     * Opcionalmente encola el trabajo de notificación formal al consumidor.
     */
    public function registrarRespuesta(RegistrarRespuestaRequest $request, int $id): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoModificacion($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);

        $decision = DecisionRespuesta::from($request->validated('decision'));
        $propuestaSolucion = trim((string) $request->validated('propuesta_solucion'));
        $fundamentoLegal = trim((string) $request->validated('fundamento_legal', ''));
        $autorCargo = trim((string) $request->validated('autor_cargo', ''));
        $notificar = $request->boolean('notificar_inmediatamente', true);

        // Procesar y almacenar evidencias adjuntas a la respuesta formal si existen
        $rutasAdjuntos = [];
        if ($request->hasFile('adjuntos')) {
            foreach ($request->file('adjuntos') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $path = $archivo->store("empresas/{$empresa->id}/respuestas/{$reclamacion->id}", 'local');
                $rutasAdjuntos[] = [
                    'nombre' => $nombreOriginal,
                    'path' => $path,
                    'tamano_bytes' => $archivo->getSize(),
                    'mime_type' => $archivo->getClientMimeType(),
                ];
            }
        }

        // Determinar siguiente versión de respuesta para este expediente
        $ultimaVersion = $reclamacion->respuestas()->max('version') ?? 0;
        $nuevaVersion = $ultimaVersion + 1;

        $respuesta = DB::transaction(function () use ($reclamacion, $empresa, $decision, $propuestaSolucion, $fundamentoLegal, $autorCargo, $rutasAdjuntos, $nuevaVersion, $request) {
            $user = $request->user();

            $nuevaRespuesta = ReclamacionRespuesta::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacion->id,
                'user_id' => $user?->id,
                'autor_nombre' => $user?->name ?? 'Responsable de Atención',
                'autor_cargo' => $autorCargo !== '' ? $autorCargo : 'Atención al Cliente',
                'decision' => $decision,
                'propuesta_solucion' => $propuestaSolucion,
                'fundamento_legal' => $fundamentoLegal !== '' ? $fundamentoLegal : null,
                'version' => $nuevaVersion,
                'adjuntos_paths' => $rutasAdjuntos,
                'fecha_respuesta' => now(),
                'notificado' => false,
            ]);

            // Actualizar automáticamente el estado del expediente a "Atendida" si estaba en pendiente o en trámite
            if (in_array($reclamacion->estado, [EstadoReclamacion::Pendiente, EstadoReclamacion::EnTramite], true)) {
                $reclamacion->update([
                    'estado' => EstadoReclamacion::Atendida,
                ]);
            }

            // Registrar evento de auditoría de respuesta registrada
            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacion->id,
                'actor_tipo' => 'usuario',
                'actor_id' => $user?->id,
                'actor_nombre' => $user?->name ?? 'Usuario interno',
                'accion' => 'respuesta_formal_registrada',
                'descripcion' => "Respuesta formal registrada (v{$nuevaVersion}) con sentido: '{$decision->label()}'. Autor: {$nuevaRespuesta->autor_nombre}.",
                'datos_despues' => [
                    'decision' => $decision->value,
                    'version' => $nuevaVersion,
                    'adjuntos_count' => count($rutasAdjuntos),
                ],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return $nuevaRespuesta;
        });

        // Encolar notificación formal al correo del consumidor
        if ($notificar) {
            dispatch(new EnviarRespuestaReclamacionJob($reclamacion, $respuesta));
        }

        app(AuditoriaService::class)->registrar(
            'respuesta_formal_registrada',
            'expedientes',
            "Respuesta formal emitida para expediente {$reclamacion->numero_hoja} (Sentido: '{$decision->label()}', v{$respuesta->version})",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'reclamacion_id' => $reclamacion->id,
                    'decision' => $decision->value,
                    'version' => $respuesta->version,
                ],
            ]
        );

        return back()->with('success', 'Respuesta formal registrada exitosamente y encolada para notificación al consumidor.');
    }

    /**
     * Reintenta manualmente la notificación por correo de la respuesta formal.
     */
    public function reenviarNotificacionRespuesta(Request $request, int $id, int $respuestaId): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $this->validarAccesoModificacion($request, $empresa);

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->findOrFail($id);
        $respuesta = ReclamacionRespuesta::where('empresa_id', $empresa->id)
            ->where('reclamacion_id', $reclamacion->id)
            ->findOrFail($respuestaId);

        dispatch(new EnviarRespuestaReclamacionJob($reclamacion, $respuesta));

        return back()->with('success', 'Se ha reintentado el envío de la notificación formal al consumidor.');
    }

    /**
     * Bloquea terminantemente la eliminación destructiva de expedientes.
     * Garantiza el cumplimiento de la política de custodia inmutable según normativa legal de protección al consumidor.
     */
    public function destroy(int $id): never
    {
        abort(403, 'La eliminación destructiva de hojas de reclamaciones está estrictamente prohibida por el D.S. N° 011-2011-PCM y la Ley N° 29571. Los expedientes deben conservarse íntegros e inmutables.');
    }
}
