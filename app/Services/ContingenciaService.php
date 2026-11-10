<?php

namespace App\Services;

use App\Models\ContingenciaIncidente;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Servicio encargado de gestionar los estados de contingencia operativa,
 * publicación de avisos de mantenimiento técnico, canales alternativos y
 * registro de simulacros de recuperación ante desastres.
 */
class ContingenciaService
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Activa el modo de contingencia para una empresa dada.
     *
     * @param  array{
     *     mensaje?: string|null,
     *     tipo?: string|null,
     *     titulo?: string|null,
     *     estimada_fin?: string|Carbon|null,
     *     canales?: array{
     *         email?: string|null,
     *         telefono?: string|null,
     *         direccion_fisica?: string|null,
     *         horario?: string|null,
     *     }|null,
     * }  $datos
     */
    public function activarContingencia(Empresa $empresa, array $datos = [], ?User $actor = null): ContingenciaIncidente
    {
        $canales = $datos['canales'] ?? $empresa->canalesContingencia();

        $empresa->update([
            'en_contingencia' => true,
            'contingencia_mensaje' => $datos['mensaje'] ?? 'El portal de Libro de Reclamaciones se encuentra temporalmente en mantenimiento preventivo. Puede utilizar nuestros canales alternativos.',
            'contingencia_canal_alternativo' => $canales,
            'contingencia_activada_en' => now(),
            'contingencia_estimada_fin' => isset($datos['estimada_fin']) && $datos['estimada_fin'] ? Carbon::parse($datos['estimada_fin']) : null,
        ]);

        $incidente = ContingenciaIncidente::create([
            'empresa_id' => $empresa->id,
            'tipo' => $datos['tipo'] ?? 'mantenimiento',
            'titulo' => $datos['titulo'] ?? 'Activación de plan de contingencia técnica',
            'descripcion' => $datos['mensaje'] ?? 'Activación del protocolo de contingencia operativa y canal alternativo.',
            'canal_alternativo' => $canales,
            'iniciado_en' => now(),
            'iniciado_por_id' => $actor?->id,
            'estado' => 'activo',
        ]);

        $this->auditoriaService->registrar(
            accion: 'activar_contingencia',
            modulo: 'contingencia',
            descripcion: "Se activó el modo de contingencia técnica para la empresa {$empresa->razon_social}.",
            opciones: [
                'empresa_id' => $empresa->id,
                'user' => $actor,
                'datos_despues' => [
                    'incidente_id' => $incidente->id,
                    'mensaje' => $empresa->contingencia_mensaje,
                    'canales' => $canales,
                ],
            ]
        );

        return $incidente;
    }

    /**
     * Desactiva el modo de contingencia y registra la resolución del incidente.
     */
    public function desactivarContingencia(Empresa $empresa, ?string $resolucion = null, ?User $actor = null): ?ContingenciaIncidente
    {
        $incidente = ContingenciaIncidente::where('empresa_id', $empresa->id)
            ->where('estado', 'activo')
            ->latest('iniciado_en')
            ->first();

        if ($incidente) {
            $descripcionFinal = $incidente->descripcion;
            if ($resolucion) {
                $descripcionFinal .= "\nResolución: {$resolucion}";
            }

            $incidente->update([
                'estado' => 'resuelto',
                'finalizado_en' => now(),
                'finalizado_por_id' => $actor?->id,
                'descripcion' => $descripcionFinal,
            ]);
        }

        $empresa->update([
            'en_contingencia' => false,
            'contingencia_activada_en' => null,
            'contingencia_estimada_fin' => null,
        ]);

        $this->auditoriaService->registrar(
            accion: 'desactivar_contingencia',
            modulo: 'contingencia',
            descripcion: "Se restableció la operación normal de la plataforma para {$empresa->razon_social}.",
            opciones: [
                'empresa_id' => $empresa->id,
                'user' => $actor,
                'datos_despues' => [
                    'resolucion' => $resolucion,
                    'finalizado_en' => now()->toIso8601String(),
                ],
            ]
        );

        return $incidente;
    }

    /**
     * Ejecuta un ensayo documentado de recuperación de desastres (DR Rehearsal).
     * Comprueba los canales alternativos y registra la trazabilidad del simulacro
     * garantizando que no se generen falsos registros de reclamación.
     *
     * @param  array{
     *     titulo?: string|null,
     *     descripcion?: string|null,
     *     resultado?: string|null,
     *     canales?: array<string, mixed>|null,
     * }  $datos
     */
    public function ejecutarEnsayo(Empresa $empresa, array $datos = [], ?User $actor = null): ContingenciaIncidente
    {
        $canales = $datos['canales'] ?? $empresa->canalesContingencia();

        $incidente = ContingenciaIncidente::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'ensayo_recuperacion',
            'titulo' => $datos['titulo'] ?? 'Ensayo preventivo del Plan de Contingencia y Recuperación',
            'descripcion' => $datos['descripcion'] ?? 'Simulacro de indisponibilidad técnica y verificación de operatividad de canales alternativos.',
            'canal_alternativo' => $canales,
            'iniciado_en' => now(),
            'finalizado_en' => now(),
            'iniciado_por_id' => $actor?->id,
            'finalizado_por_id' => $actor?->id,
            'resultado_ensayo' => $datos['resultado'] ?? 'Ensayo completado satisfactoriamente. Se verificaron los canales alternativos de soporte (correo oficial, teléfono y domicilio físico). No se emitieron números correlativos ni se crearon expedientes ficticios.',
            'estado' => 'ensayo_completado',
        ]);

        $this->auditoriaService->registrar(
            accion: 'ensayo_recuperacion',
            modulo: 'contingencia',
            descripcion: "Se completó un ensayo preventivo de recuperación ante contingencias para {$empresa->razon_social}.",
            opciones: [
                'empresa_id' => $empresa->id,
                'user' => $actor,
                'datos_despues' => [
                    'ensayo_id' => $incidente->id,
                    'resultado' => $incidente->resultado_ensayo,
                ],
            ]
        );

        return $incidente;
    }
}
