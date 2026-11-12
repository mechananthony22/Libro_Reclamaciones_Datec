<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitacionRequest;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class InvitacionController extends Controller
{
    public function store(StoreInvitacionRequest $request, Empresa $empresa): RedirectResponse
    {
        $rol = RolEmpresa::from($request->validated('rol'));
        $horas = $request->integer('horas_expiracion', 72);
        $email = strtolower(trim($request->validated('email')));

        // Reutilizar o invalidar invitaciones previas no aceptadas para esta misma empresa
        if ($rol === RolEmpresa::AdminEmpresa) {
            // Un solo administrador titular: invalidar cualquier invitación previa pendiente para esta empresa
            InvitacionEmpresa::where('empresa_id', $empresa->id)
                ->where('rol', RolEmpresa::AdminEmpresa->value)
                ->delete();
        } else {
            InvitacionEmpresa::where('empresa_id', $empresa->id)
                ->where('email', $email)
                ->delete();
        }

        $invitacion = InvitacionEmpresa::generarPara(
            empresa: $empresa,
            email: $email,
            nombre: $request->validated('nombre'),
            rol: $rol,
            horasExpiracion: $horas
        );

        // Si se invita a un Administrador de Empresa, mantener sincronizada la ficha de la empresa
        if ($rol === RolEmpresa::AdminEmpresa) {
            $updates = [
                'email' => $email,
                'representante_email' => $email,
            ];
            if ($request->filled('nombre')) {
                $updates['representante_nombre'] = $request->validated('nombre');
            }
            $empresa->update($updates);
        }

        $enlace = route('invitacion.show', ['token' => $invitacion->token]);

        return back()
            ->with('success', "Invitación generada exitosamente para {$invitacion->email}.")
            ->with('invitacion_url', $enlace)
            ->with('invitacion_email', $invitacion->email)
            ->with('invitacion_empresa', $empresa->name);
    }

    public function resend(Empresa $empresa, InvitacionEmpresa $invitacion): RedirectResponse
    {
        if ($invitacion->empresa_id !== $empresa->id) {
            abort(404);
        }

        $invitacion->update([
            'token' => Str::random(48),
            'expires_at' => now()->addHours(72),
            'accepted_at' => null,
        ]);

        $enlace = route('invitacion.show', ['token' => $invitacion->token]);

        return back()
            ->with('success', "Enlace de activación renovado por 72 horas para {$invitacion->email}.")
            ->with('invitacion_url', $enlace)
            ->with('invitacion_email', $invitacion->email)
            ->with('invitacion_empresa', $empresa->name);
    }
}
