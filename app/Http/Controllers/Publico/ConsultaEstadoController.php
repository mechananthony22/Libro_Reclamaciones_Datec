<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\ConsultarEstadoReclamacionRequest;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Services\PdfConstanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de consulta pública segura del estado de expedientes de reclamación.
 * Implementa control de doble factor de verificación (número de hoja / token + documento / email)
 * y generación de enlaces temporales firmados para impedir el raspado o adivinación de correlativos.
 */
class ConsultaEstadoController extends Controller
{
    /**
     * Muestra la pantalla inicial para consultar el estado del expediente.
     */
    public function show(string $slug): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        return Inertia::render('Publico/ConsultaEstado', [
            'empresa' => [
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'logo_path' => $empresa->logo_path,
                'slug' => $empresa->slug,
            ],
            'expediente' => null,
            'url_firmada' => null,
        ]);
    }

    /**
     * Procesa la consulta validando el código y la credencial secundaria (documento o correo).
     */
    public function consultar(ConsultarEstadoReclamacionRequest $request, string $slug): Response|RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $codigo = trim($request->validated('codigo'));
        $verificacion = trim(strtolower($request->validated('verificacion')));

        // Buscar el expediente exclusivamente dentro de la empresa actual (aislamiento tenant)
        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where(function ($q) use ($codigo) {
                $q->where('numero_hoja', $codigo)
                    ->orWhere('token_consulta', $codigo);
            })
            ->withCount('adjuntos')
            ->first();

        // Si no existe el expediente en este tenant, retornar error genérico
        if (! $reclamacion) {
            return back()->withErrors([
                'codigo' => 'No se encontró ningún expediente con los datos proporcionados.',
            ])->withInput();
        }

        // Doble verificación: contrastar número de documento o correo electrónico
        $consumidor = $reclamacion->datos_consumidor ?? [];
        $documentoRegistrado = trim(strtolower((string) ($consumidor['numero_documento'] ?? '')));
        $emailRegistrado = trim(strtolower((string) ($consumidor['email'] ?? '')));

        if ($verificacion !== $documentoRegistrado && $verificacion !== $emailRegistrado) {
            return back()->withErrors([
                'verificacion' => 'El documento o correo no coincide con el registrado en este expediente.',
            ])->withInput();
        }

        // Generar enlace temporal firmado válido por 30 minutos para acceso seguro sin login
        $urlFirmada = URL::temporarySignedRoute(
            'libro.consulta.firmada',
            now()->addMinutes(30),
            [
                'slug' => $empresa->slug,
                'token' => $reclamacion->token_consulta,
            ]
        );

        $payloadExpediente = $this->transformarExpedienteSeguro($reclamacion, $empresa);

        return Inertia::render('Publico/ConsultaEstado', [
            'empresa' => [
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'slug' => $empresa->slug,
            ],
            'expediente' => $payloadExpediente,
            'url_firmada' => $urlFirmada,
        ]);
    }

    /**
     * Muestra el detalle del estado de la reclamación a través de un enlace temporal firmado.
     */
    public function detalleFirmado(Request $request, string $slug, string $token): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'El enlace de consulta ha expirado o es inválido. Por favor realiza una nueva búsqueda.');
        }

        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $reclamacion = Reclamacion::where('empresa_id', $empresa->id)
            ->where('token_consulta', $token)
            ->withCount('adjuntos')
            ->firstOrFail();

        $payloadExpediente = $this->transformarExpedienteSeguro($reclamacion, $empresa);

        return Inertia::render('Publico/ConsultaEstado', [
            'empresa' => [
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'logo_path' => $empresa->logo_path,
                'slug' => $empresa->slug,
            ],
            'expediente' => $payloadExpediente,
            'url_firmada' => $request->fullUrl(),
        ]);
    }

    /**
     * Descarga la constancia en PDF validando que la solicitud provenga de un enlace firmado temporal vigente.
     */
    public function descargarPdfFirmado(Request $request, string $slug, string $token, PdfConstanciaService $pdfService): HttpResponse
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

    /**
     * Sanitiza y estructura la información del expediente para exhibición segura
     * protegiendo datos personales innecesarios frente a terceros.
     */
    protected function transformarExpedienteSeguro(Reclamacion $reclamacion, Empresa $empresa): array
    {
        $consumidor = $reclamacion->datos_consumidor ?? [];
        $nombreCompleto = trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? ''));

        // Enmascaramiento de privacidad en correo (ej. j***n@ejemplo.com)
        $email = $consumidor['email'] ?? '';
        $emailEnmascarado = $this->enmascararCorreo($email);

        // Obtener la última respuesta formal si existe
        $ultimaRespuesta = $reclamacion->respuestas()->latest('version')->first();
        $respuestaData = null;

        if ($ultimaRespuesta) {
            $respuestaData = [
                'decision' => $ultimaRespuesta->decision?->value,
                'decision_label' => $ultimaRespuesta->decision?->label() ?? 'Atendido',
                'propuesta_solucion' => $ultimaRespuesta->propuesta_solucion,
                'fundamento_legal' => $ultimaRespuesta->fundamento_legal,
                'autor_nombre' => $ultimaRespuesta->autor_nombre,
                'autor_cargo' => $ultimaRespuesta->autor_cargo,
                'version' => $ultimaRespuesta->version,
                'fecha_respuesta' => $ultimaRespuesta->fecha_respuesta?->format('d/m/Y H:i:s') ?? $ultimaRespuesta->created_at?->format('d/m/Y H:i:s'),
                'adjuntos' => $ultimaRespuesta->adjuntos_paths ?? [],
            ];
        }

        return [
            'numero_hoja' => $reclamacion->numero_hoja,
            'tipo' => $reclamacion->tipo->value,
            'tipo_label' => $reclamacion->tipo->label(),
            'tipo_bien' => $reclamacion->tipo_bien->label(),
            'descripcion_bien' => $reclamacion->descripcion_bien,
            'monto_reclamado' => $reclamacion->monto_reclamado ? "{$reclamacion->moneda} {$reclamacion->monto_reclamado}" : null,
            'estado' => $reclamacion->estado->value,
            'estado_label' => ucfirst($reclamacion->estado->value),
            'fecha_hora' => $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s'),
            'vence_en' => $reclamacion->vence_en?->format('d/m/Y') ?? '',
            'consumidor_nombre' => $nombreCompleto,
            'consumidor_email' => $emailEnmascarado,
            'consumidor_documento' => strtoupper($consumidor['tipo_documento'] ?? 'DNI').': '.$this->enmascararDocumento($consumidor['numero_documento'] ?? ''),
            'detalle_problema' => $reclamacion->detalle_problema,
            'pedido_consumidor' => $reclamacion->pedido_consumidor,
            'token_consulta' => $reclamacion->token_consulta,
            'adjuntos_count' => $reclamacion->adjuntos_count ?? 0,
            'respuesta' => $respuestaData,
            'url_descarga_pdf' => URL::temporarySignedRoute('libro.constancia.pdf', now()->addHours(2), [
                'slug' => $empresa->slug,
                'token' => $reclamacion->token_consulta,
            ]),
        ];
    }

    /**
     * Enmascara el correo electrónico para proteger la privacidad del reclamante.
     */
    protected function enmascararCorreo(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '***';
        }

        [$usuario, $dominio] = explode('@', $email, 2);
        $len = strlen($usuario);

        if ($len <= 2) {
            $usuarioOculto = $usuario[0].'*';
        } else {
            $usuarioOculto = $usuario[0].str_repeat('*', $len - 2).$usuario[$len - 1];
        }

        return $usuarioOculto.'@'.$dominio;
    }

    /**
     * Enmascara el número de documento de identidad mostrando solo los últimos dígitos.
     */
    protected function enmascararDocumento(string $documento): string
    {
        $len = strlen($documento);
        if ($len <= 4) {
            return $documento;
        }

        return str_repeat('*', $len - 3).substr($documento, -3);
    }
}
