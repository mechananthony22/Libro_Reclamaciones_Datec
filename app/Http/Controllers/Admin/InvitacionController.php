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

        $invitacion = InvitacionEmpresa::generarPara(
            empresa: $empresa,
            email: $request->validated('email'),
            nombre: $request->validated('nombre'),
            rol: $rol,
            horasExpiracion: $horas
        );

        $enlace = route('invitacion.show', ['token' => $invitacion->token]);

        return back()
            ->with('success', "Invitación generada exitosamente para {$invitacion->email}.")
            ->with('invitacion_url', $enlace);
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
            ->with('invitacion_url', $enlace);
    }
}

