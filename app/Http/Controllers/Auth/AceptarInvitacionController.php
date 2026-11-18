<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RolEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AceptarInvitacionRequest;
use App\Models\InvitacionEmpresa;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $empresa = $invitacion->empresa;
        $emailNuevo = strtolower(trim($invitacion->email));

        $user = DB::transaction(function () use ($request, $invitacion, $empresa, $emailNuevo) {
            if ($invitacion->rol === RolEmpresa::AdminEmpresa) {
                // 1. Buscar si la empresa ya tiene un administrador registrado
                $adminActual = $empresa->usuarios()
                    ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
                    ->first();

                // 2. Crear o actualizar la cuenta del nuevo usuario sin sobrescribir ni desplazar la identidad del titular previo
                $user = User::where('email', $emailNuevo)->first() ?: new User(['email' => $emailNuevo]);
                $user->name = $request->validated('name');
                $user->password = Hash::make($request->validated('password'));
                $user->save();

                // 3. Desvincular al administrador anterior del tenant preservando su cuenta y registro pericial
                if ($adminActual && $adminActual->id !== $user->id) {
                    $empresa->usuarios()->detach($adminActual->id);
                }

                // Desvincular cualquier otro usuario que tuviera rol admin_empresa en este tenant
                $otrosAdmins = $empresa->usuarios()
                    ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
                    ->where('users.id', '!=', $user->id)
                    ->get();

                foreach ($otrosAdmins as $otro) {
                    $empresa->usuarios()->detach($otro->id);
                }

                // Vincular/asegurar que este usuario es el único admin_empresa activo de la empresa
                $empresa->usuarios()->syncWithoutDetaching([
                    $user->id => [
                        'rol' => RolEmpresa::AdminEmpresa->value,
                        'activo' => true,
                    ],
                ]);

                // Sincronizar datos oficiales en la ficha de la empresa
                $empresa->update([
                    'representante_nombre' => $user->name,
                    'representante_email' => $user->email,
                    'email' => $user->email,
                ]);
            } else {
                // Flujo estándar para operadores / supervisores de empresa
                $user = User::firstOrNew(['email' => $emailNuevo]);
                $user->name = $request->validated('name');
                $user->password = Hash::make($request->validated('password'));
                $user->save();

                $empresa->usuarios()->syncWithoutDetaching([
                    $user->id => [
                        'rol' => $invitacion->rol->value,
                        'activo' => true,
                    ],
                ]);
            }

            // Marcar la invitación como aceptada
            $invitacion->update(['accepted_at' => now()]);

            // Auditoría pericial de aceptación de invitación
            app(AuditoriaService::class)->registrar(
                'invitacion_aceptada',
                'usuarios',
                "El usuario {$user->name} ({$user->email}) aceptó la invitación con rol \"{$invitacion->rol->label()}\" para la empresa {$empresa->name}.",
                [
                    'empresa_id' => $empresa->id,
                    'user' => $user,
                    'actor_tipo' => 'usuario',
                    'actor_nombre' => $user->name,
                    'actor_email' => $user->email,
                    'datos_despues' => [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'rol' => $invitacion->rol->value,
                        'empresa_id' => $empresa->id,
                    ],
                    'request' => $request,
                ]
            );

            return $user;
        });

        // Cerrar cualquier sesión previa para garantizar un inicio limpio
        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Redirigir al formulario de inicio de sesión
        return redirect()->route('login')
            ->with('success', "¡Cuenta para {$empresa->name} activada con éxito! Ingresa con tu correo y contraseña.");
    }
}
