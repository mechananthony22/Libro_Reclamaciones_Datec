<?php

namespace App\Http\Controllers\Publico;

use App\Actions\Reclamaciones\RegistrarReclamacionAction;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Enums\TipoReclamacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\RegistrarReclamacionRequest;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Services\PdfConstanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\URL;
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
    public function index(string $slug): Response|\Symfony\Component\HttpFoundation\Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        // Si la empresa se encuentra bajo modo de contingencia o mantenimiento operativo
        if ($empresa->estaEnContingencia()) {
            return Inertia::render('Publico/Contingencia', [
                'empresa' => [
                    'name' => $empresa->name,
                    'razon_social' => $empresa->razon_social,
                    'ruc' => $empresa->ruc,
                    'logo_path' => $empresa->logo_path,
                    'color_primario' => $empresa->color_primario ?: '#2563EB',
                    'slug' => $empresa->slug,
                ],
                'contingencia' => [
                    'mensaje' => $empresa->contingencia_mensaje ?: 'El portal de Libro de Reclamaciones se encuentra temporalmente en mantenimiento preventivo.',
                    'canales' => $empresa->canalesContingencia(),
                    'activada_en' => $empresa->contingencia_activada_en?->toIso8601String(),
                    'estimada_fin' => $empresa->contingencia_estimada_fin?->toIso8601String(),
                ],
            ])->toResponse(request())->setStatusCode(503);
        }

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

        if ($empresa->estaEnContingencia()) {
            abort(503, $empresa->contingencia_mensaje ?: 'El servicio de registro se encuentra temporalmente en contingencia. Por favor utilice nuestros canales alternativos.');
        }

        $validated = $request->validated();

        $adjuntos = $request->hasFile('adjuntos') ? $request->file('adjuntos') : [];

        $reclamacion = app(RegistrarReclamacionAction::class)->ejecutar(
            $empresa,
            $validated,
            $adjuntos,
            $request->ip(),
            $request->userAgent()
        );

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
                'vence_en' => $reclamacion->vence_en?->format('d/m/Y') ?? '',
                'consumidor_nombre' => $nombreConsumidor,
                'consumidor_email' => $consumidor['email'] ?? '',
                'token_consulta' => $reclamacion->token_consulta,
                'adjuntos_count' => $reclamacion->adjuntos_count,
            ],
            'url_descarga_pdf' => URL::temporarySignedRoute('libro.constancia.pdf', now()->addHours(24), [
                'slug' => $empresa->slug,
                'token' => $reclamacion->token_consulta,
            ]),
        ]);
    }

    /**
     * Genera y descarga el archivo PDF oficial con la constancia de la reclamación,
     * exigiendo un enlace firmado temporal para proteger los datos personales del consumidor.
     */
    public function descargarPdf(Request $request, string $slug, string $token, PdfConstanciaService $pdfService): HttpResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'El enlace de descarga de la constancia ha expirado o es inválido por motivos de seguridad y protección de datos personales. Por favor genera un nuevo enlace desde la consulta de estado.');
        }

        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where('token_consulta', $token)
            ->firstOrFail();

        return $pdfService->descargarPdf($reclamacion);
    }
}
