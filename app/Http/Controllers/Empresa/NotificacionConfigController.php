<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de configuración de correos de notificación y preferencias de alerta de la empresa.
 * Permite definir destinatarios adicionales y ajustar alertas preventivas sin desactivar avisos críticos de ley.
 */
class NotificacionConfigController extends Controller
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Muestra la pantalla de configuración de notificaciones del tenant.
     */
    public function edit(): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        return Inertia::render('Empresa/Notificaciones/Edit', [
            'configuracion' => [
                'emails_notificaciones' => $empresa->emails_notificaciones ?? [],
                'notificar_nuevo_reclamo' => (bool) ($empresa->notificar_nuevo_reclamo ?? true),
                'notificar_vencimiento_preventivo' => (bool) ($empresa->notificar_vencimiento_preventivo ?? true),
                'notificar_vencido' => true, // Aviso crítico normativo obligatorio e inalterable
                'dias_anticipacion_alerta' => (int) ($empresa->dias_anticipacion_alerta ?? 3),
                'email_oficial' => $empresa->email,
                'representante_email' => $empresa->representante_email,
            ],
        ]);
    }

    /**
     * Actualiza las preferencias de notificación garantizando que afecten solo a la empresa correspondiente.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $datos = $request->validate([
            'emails_notificaciones' => ['nullable', 'array'],
            'emails_notificaciones.*' => ['required', 'email', 'max:150'],
            'notificar_nuevo_reclamo' => ['required', 'boolean'],
            'notificar_vencimiento_preventivo' => ['required', 'boolean'],
            'dias_anticipacion_alerta' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // Filtrar y normalizar correos válidos
        $emailsLimpios = array_values(array_unique(array_filter(
            $datos['emails_notificaciones'] ?? [],
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        )));

        $datosAntes = [
            'emails_notificaciones' => $empresa->emails_notificaciones,
            'notificar_nuevo_reclamo' => $empresa->notificar_nuevo_reclamo,
            'notificar_vencimiento_preventivo' => $empresa->notificar_vencimiento_preventivo,
            'dias_anticipacion_alerta' => $empresa->dias_anticipacion_alerta,
        ];

        // Guardar configuración manteniendo notificar_vencido permanentemente en true por cumplimiento de ley
        $empresa->update([
            'emails_notificaciones' => $emailsLimpios,
            'notificar_nuevo_reclamo' => $datos['notificar_nuevo_reclamo'],
            'notificar_vencimiento_preventivo' => $datos['notificar_vencimiento_preventivo'],
            'notificar_vencido' => true,
            'dias_anticipacion_alerta' => $datos['dias_anticipacion_alerta'],
        ]);

        $this->auditoriaService->registrar(
            'configuracion_notificaciones_actualizada',
            'configuracion',
            "Preferencias de correos y alertas actualizadas para {$empresa->name} (Anticipación: {$empresa->dias_anticipacion_alerta} días hábiles)",
            [
                'empresa_id' => $empresa->id,
                'datos_antes' => $datosAntes,
                'datos_despues' => [
                    'emails_notificaciones' => $emailsLimpios,
                    'notificar_nuevo_reclamo' => $empresa->notificar_nuevo_reclamo,
                    'notificar_vencimiento_preventivo' => $empresa->notificar_vencimiento_preventivo,
                    'dias_anticipacion_alerta' => $empresa->dias_anticipacion_alerta,
                ],
            ]
        );

        return back()->with('success', 'Preferencias de correos y alertas actualizadas correctamente.');
    }
}
