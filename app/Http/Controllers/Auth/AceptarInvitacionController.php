<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AceptarInvitacionRequest;
use App\Models\InvitacionEmpresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AceptarInvitacionController extends Controller
{
    public function show(string $token): Response
    {
        /** @var InvitacionEmpresa|null $invitacion */
        $invitacion = InvitacionEmpresa::where('token', $token)
            ->with('empresa')
            ->first();

        if (! $invitacion) {
            return Inertia::render('Auth/InvitacionInvalida', [
                'motivo' => 'El enlace de invitación no existe o es inválido.',
            ]);
        }

        if ($invitacion->isAccepted()) {
            return Inertia::render('Auth/InvitacionInvalida', [
                'motivo' => 'Esta invitación ya fue aceptada anteriormente. Puedes iniciar sesión con tu cuenta.',
            ]);
        }

        if ($invitacion->isExpired()) {
            return Inertia::render('Auth/InvitacionInvalida', [
                'motivo' => 'Este enlace de invitación ha expirado. Solicita al administrador de Datec que te reenvíe una nueva invitación.',
            ]);
        }

        return Inertia::render('Auth/AceptarInvitacion', [
            'token' => $token,
            'email' => $invitacion->email,
            'nombreSugerido' => $invitacion->nombre,
            'rolLabel' => $invitacion->rol->label(),
            'empresa' => [
                'id' => $invitacion->empresa->id,
                'name' => $invitacion->empresa->name,
                'razon_social' => $invitacion->empresa->razon_social,
                'ruc' => $invitacion->empresa->ruc,
                'slug' => $invitacion->empresa->slug,
            ],
        ]);
    }

    public function store(AceptarInvitacionRequest $request, string $token): RedirectResponse
    {
        /** @var InvitacionEmpresa|null $invitacion */
        $invitacion = InvitacionEmpresa::where('token', $token)
            ->with('empresa')
            ->first();

        if (! $invitacion || ! $invitacion->isValid()) {
            return redirect()
                ->route('invitacion.show', ['token' => $token])
                ->withErrors(['token' => 'La invitación ya no es válida o ha expirado.']);
        }

        // Buscar o crear el usuario
        $user = User::firstOrNew(['email' => strtolower($invitacion->email)]);
        $user->name = $request->validated('name');
        $user->password = Hash::make($request->validated('password'));
        $user->save();

        // Asociar al tenant con su rol autorizado
        $empresa = $invitacion->empresa;
        if (! $empresa->usuarios()->where('user_id', $user->id)->exists()) {
            $empresa->usuarios()->attach($user->id, [
                'rol' => $invitacion->rol->value,
                'activo' => true,
            ]);
        } else {
            $empresa->usuarios()->updateExistingPivot($user->id, [
                'rol' => $invitacion->rol->value,
                'activo' => true,
            ]);
        }

        // Marcar la invitación como aceptada
        $invitacion->update(['accepted_at' => now()]);

        // Autenticar la sesión del usuario
        Auth::login($user);

        return redirect()->route('home')
            ->with('success', "¡Bienvenido a Datec Libro! Tu cuenta para {$empresa->name} ha sido activada con éxito.");
    }
}

