<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\EstadoEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\UpdateIdentidadLegalRequest;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IdentidadLegalController extends Controller
{
    /**
     * Muestra la pantalla de configuración de identidad legal de la empresa (RF-008).
     */
    public function edit(Request $request): Response
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        // En caso de que aún no se haya inicializado en sesión, tomar la empresa del usuario
        if (! $empresa) {
            $user = $request->user();
            if ($user) {
                $empresa = $user->empresas()->first();
                $empresa?->makeCurrent();
            }

            if (! $empresa && $request->has('empresa_id') && $user?->isSuperAdmin()) {
                $empresa = Empresa::find($request->integer('empresa_id'));
                $empresa?->makeCurrent();
            }

            if (! $empresa) {
                $empresa = Empresa::first();
                $empresa?->makeCurrent();
            }
        }

        if (! $empresa) {
            abort(404, 'No se encontró la empresa.');
        }

        return Inertia::render('Empresa/IdentidadLegal/Edit', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'slug' => $empresa->slug,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'direccion' => $empresa->direccion,
                'email' => $empresa->email,
                'telefono' => $empresa->telefono,
                'representante_nombre' => $empresa->representante_nombre,
                'representante_email' => $empresa->representante_email,
                'representante_telefono' => $empresa->representante_telefono,
                'color_primario' => $empresa->color_primario,
                'estado' => $empresa->estado?->value,
                'activo' => $empresa->activo,
            ],
            'checklist' => $empresa->checklistMinimosLegales(),
            'tieneMinimos' => $empresa->tieneMinimosLegales(),
            'urlPublica' => url("/l/{$empresa->slug}"),
        ]);
    }

    /**
     * Actualiza la identidad legal y valida la regla de activación pública (RF-008).
     */
    public function update(UpdateIdentidadLegalRequest $request): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada en el contexto actual.');
        }

        $datos = $request->validated();
        $solicitarActivacion = $request->boolean('solicitar_activacion');
        unset($datos['solicitar_activacion']);

        $empresa->fill($datos);

        // Criterio de aceptación RF-008: Sin mínimos legales completos, no se podrá activar el formulario público
        if ($solicitarActivacion) {
            if (! $empresa->tieneMinimosLegales()) {
                throw ValidationException::withMessages([
                    'solicitar_activacion' => 'No es posible activar el formulario público del Libro: se requiere registrar obligatoriamente RUC de 11 dígitos, Razón Social válida y Domicilio Legal conforme a INDECOPI (RN-002).',
                ]);
            }

            $empresa->estado = EstadoEmpresa::Activa;
            $empresa->activo = true;
            $empresa->motivo_cambio_estado = 'Activación de servicio tras completar requisitos legales mínimos (RF-008).';
            $empresa->fecha_cambio_estado = now();
        }

        $empresa->save();

        app(AuditoriaService::class)->registrar(
            'identidad_legal_actualizada',
            'configuracion',
            "Edición de configuración legal de la empresa {$empresa->name} (RUC: {$empresa->ruc})",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => $datos,
            ]
        );

        $mensaje = $solicitarActivacion
            ? '¡Configuración legal guardada! El Libro de Reclamaciones ha sido activado y publicado con éxito.'
            : "Identidad legal de {$empresa->name} actualizada correctamente.";

        return back()->with('success', $mensaje);
    }
}
