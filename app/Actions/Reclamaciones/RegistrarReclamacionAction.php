<?php

namespace App\Actions\Reclamaciones;

use App\Enums\EstadoReclamacion;
use App\Jobs\EnviarConstanciaReclamacionJob;
use App\Jobs\NotificarNuevaReclamacionEmpresaJob;
use App\Models\Empresa;
use App\Models\Reclamacion;
use App\Models\ReclamacionAdjunto;
use App\Models\ReclamacionEvento;
use App\Services\CorrelativoService;
use App\Services\PlazoReclamacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Caso de uso desacoplado para el registro formal de reclamaciones y quejas.
 * Reutilizable tanto por la interfaz web de Inertia como por endpoints de API REST.
 */
class RegistrarReclamacionAction
{
    public function __construct(
        protected CorrelativoService $correlativoService,
        protected PlazoReclamacionService $plazoService
    ) {}

    /**
     * Ejecuta el registro atómico de una reclamación o queja.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, UploadedFile>  $adjuntos
     */
    public function ejecutar(
        Empresa $empresa,
        array $datos,
        array $adjuntos = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): Reclamacion {
        // Asegurar que el contexto multitenant esté vinculado para jobs en cola
        if (! Empresa::current() || Empresa::current()->id !== $empresa->id) {
            $empresa->makeCurrent();
        }

        // Estructurar los datos de identificación y contacto del reclamante
        $datosConsumidor = [
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => trim($datos['numero_documento']),
            'nombres' => trim($datos['nombres']),
            'apellidos' => isset($datos['apellidos']) ? trim($datos['apellidos']) : null,
            'email' => trim(strtolower($datos['email'])),
            'telefono' => trim($datos['telefono']),
            'direccion' => trim($datos['direccion']),
            'departamento' => $datos['departamento'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'distrito' => $datos['distrito'] ?? null,
            'es_menor_edad' => (bool) ($datos['es_menor_edad'] ?? false),
        ];

        if ($datosConsumidor['es_menor_edad']) {
            $datosConsumidor['apoderado'] = [
                'nombre' => trim($datos['apoderado_nombre'] ?? ''),
                'tipo_documento' => $datos['apoderado_tipo_documento'] ?? null,
                'numero_documento' => trim($datos['apoderado_numero_documento'] ?? ''),
                'telefono' => trim($datos['apoderado_telefono'] ?? ''),
                'email' => trim(strtolower($datos['apoderado_email'] ?? '')),
            ];
        }

        // Instantánea legal inmutable de la empresa proveedora
        $datosLegalesSnapshot = [
            'razon_social' => $empresa->razon_social,
            'ruc' => $empresa->ruc,
            'direccion' => $empresa->direccion,
            'email' => $empresa->email,
            'telefono' => $empresa->telefono,
            'capturado_en' => now()->toIso8601String(),
        ];

        return DB::transaction(function () use ($empresa, $datos, $datosConsumidor, $datosLegalesSnapshot, $adjuntos, $ip, $userAgent) {
            $numeroHoja = $this->correlativoService->generarProximoNumero(
                $empresa,
                $datos['establecimiento_id'] ?? null
            );

            $fechaHora = now();
            $venceEn = $this->plazoService->calcularFechaVencimiento($fechaHora, 15);

            $reclamacion = Reclamacion::create([
                'empresa_id' => $empresa->id,
                'establecimiento_id' => $datos['establecimiento_id'] ?? null,
                'numero_hoja' => $numeroHoja,
                'fecha_hora' => $fechaHora,
                'tipo' => $datos['tipo'],
                'tipo_bien' => $datos['tipo_bien'],
                'monto_reclamado' => $datos['monto_reclamado'] ?? null,
                'moneda' => $datos['moneda'] ?? 'PEN',
                'descripcion_bien' => trim($datos['descripcion_bien']),
                'numero_comprobante' => isset($datos['numero_comprobante']) ? trim($datos['numero_comprobante']) : null,
                'fecha_contratacion' => $datos['fecha_contratacion'] ?? null,
                'detalle_problema' => trim($datos['detalle_problema']),
                'pedido_consumidor' => trim($datos['pedido_consumidor']),
                'datos_consumidor' => $datosConsumidor,
                'datos_legales_snapshot' => $datosLegalesSnapshot,
                'aviso_privacidad_version' => $datos['aviso_privacidad_version'] ?? '1.0',
                'aviso_privacidad_aceptado_en' => now(),
                'aviso_privacidad_ip' => $ip,
                'estado' => EstadoReclamacion::Pendiente,
                'token_consulta' => Str::random(40),
                'vence_en' => $venceEn,
            ]);

            // Auditoría inmutable de presentación
            ReclamacionEvento::create([
                'empresa_id' => $empresa->id,
                'reclamacion_id' => $reclamacion->id,
                'actor_tipo' => 'consumidor',
                'actor_id' => null,
                'actor_nombre' => trim($datosConsumidor['nombres'].' '.($datosConsumidor['apellidos'] ?? '')),
                'accion' => 'presentacion_inicial',
                'descripcion' => "Presentación formal de la {$reclamacion->tipo->label()} con número correlativo {$reclamacion->numero_hoja}.",
                'datos_antes' => null,
                'datos_despues' => [
                    'numero_hoja' => $reclamacion->numero_hoja,
                    'tipo' => $reclamacion->tipo->value,
                    'tipo_bien' => $reclamacion->tipo_bien->value,
                    'consumidor' => $datosConsumidor,
                    'detalle_problema' => $reclamacion->detalle_problema,
                    'pedido_consumidor' => $reclamacion->pedido_consumidor,
                    'proveedor_snapshot' => $datosLegalesSnapshot,
                ],
                'ip' => $ip,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            // Almacenar archivos adjuntos si existen
            foreach ($adjuntos as $archivo) {
                if ($archivo instanceof UploadedFile && $archivo->isValid()) {
                    $extension = $archivo->getClientOriginalExtension();
                    $nombreAlmacenado = 'adjunto_'.Str::random(32).'.'.$extension;
                    $rutaStorage = $archivo->storeAs("evidencias/{$empresa->id}/{$reclamacion->id}", $nombreAlmacenado, 'local');

                    ReclamacionAdjunto::create([
                        'empresa_id' => $empresa->id,
                        'reclamacion_id' => $reclamacion->id,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'storage_path' => $rutaStorage,
                        'mime_type' => $archivo->getClientMimeType() ?: $archivo->getMimeType(),
                        'tamano_bytes' => $archivo->getSize(),
                        'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
                        'origen' => 'consumidor',
                    ]);
                }
            }

            // Despachar tareas asíncronas de envío de correo
            EnviarConstanciaReclamacionJob::dispatch($reclamacion);
            NotificarNuevaReclamacionEmpresaJob::dispatch($reclamacion);

            return $reclamacion;
        });
    }
}
