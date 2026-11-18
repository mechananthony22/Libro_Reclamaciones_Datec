<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\RolEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\StoreUsuarioInvitacionRequest;
use App\Http\Requests\Empresa\UpdateUsuarioRolRequest;
use App\Models\Empresa;
use App\Models\InvitacionEmpresa;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /**
     * Listado de usuarios, roles y membresías de la empresa (RF-011).
     */
    public function index(Request $request): Response
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            $user = $request->user();
            $empresa = $user?->empresas()->wherePivot('activo', true)->where('tenants.activo', true)->first();
            $empresa?->makeCurrent();
        }

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        $this->validarAdminEmpresa($request, $empresa);

        // Obtener los usuarios asociados exclusivamente a esta empresa (RF-007, RF-011)
        $usuarios = $empresa->usuarios()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'rol' => $u->pivot->rol,
                'rol_label' => RolEmpresa::tryFrom($u->pivot->rol)?->label() ?? $u->pivot->rol,
                'activo' => (bool) $u->pivot->activo,
                'vinculado_el' => $u->pivot->created_at?->format('d/m/Y'),
            ]);

        // Invitaciones pendientes de la empresa
        $invitaciones = $empresa->invitaciones()
            ->whereNull('accepted_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'email' => $inv->email,
                'nombre' => $inv->nombre,
                'rol' => $inv->rol->value,
                'rol_label' => $inv->rol->label(),
                'token' => $inv->token,
                'url' => route('invitacion.show', ['token' => $inv->token]),
                'expira_el' => $inv->expires_at->format('d/m/Y H:i'),
                'expirada' => $inv->isExpired(),
            ]);

        $tieneAdminActivo = $empresa->usuarios()
            ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
            ->wherePivot('activo', true)
            ->exists();

        $rolesDisponibles = array_map(function (RolEmpresa $r) use ($tieneAdminActivo) {
            $esAdmin = $r === RolEmpresa::AdminEmpresa;

            return [
                'value' => $r->value,
                'label' => $r->label(),
                'descripcion' => $r->descripcion(),
                'disponible_para_invitar' => ! ($esAdmin && $tieneAdminActivo),
                'motivo_bloqueo' => ($esAdmin && $tieneAdminActivo)
                    ? 'La empresa ya cuenta con un Administrador Titular activo. Para salvaguardar la titularidad legal, no es posible invitar a un segundo administrador que desplace al titular.'
                    : null,
            ];
        }, RolEmpresa::cases());

        return Inertia::render('Empresa/Usuarios/Index', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'slug' => $empresa->slug,
            ],
            'usuarios' => $usuarios,
            'invitaciones' => $invitaciones,
            'roles' => $rolesDisponibles,
            'usuarioActualId' => $request->user()?->id,
        ]);
    }

    /**
     * Generar invitación para un nuevo usuario en la empresa (RF-011).
     */
    public function invitar(StoreUsuarioInvitacionRequest $request): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        $this->validarAdminEmpresa($request, $empresa);

        $email = strtolower(trim($request->validated('email')));
        $rol = RolEmpresa::from($request->validated('rol'));

        // Criterio de seguridad legal: Prohibir invitar a un segundo administrador que desplace al titular
        if ($rol === RolEmpresa::AdminEmpresa && $empresa->usuarios()->wherePivot('rol', RolEmpresa::AdminEmpresa->value)->wherePivot('activo', true)->exists()) {
            return back()->withErrors([
                'rol' => 'La empresa ya cuenta con un Administrador Titular activo. Para salvaguardar la titularidad legal, no es posible invitar a un segundo administrador que desplace al titular.',
            ]);
        }

        // Criterio de aceptación RF-011: Comprobar si el usuario ya está activo en la empresa
        $yaEsMiembro = $empresa->usuarios()
            ->where('email', $email)
            ->wherePivot('activo', true)
            ->exists();

        if ($yaEsMiembro) {
            return back()->withErrors(['email' => 'El usuario ya es un miembro activo de esta empresa.']);
        }

        InvitacionEmpresa::where('empresa_id', $empresa->id)
            ->where('email', $email)
            ->delete();

        $invitacion = InvitacionEmpresa::generarPara(
            empresa: $empresa,
            email: $email,
            nombre: $request->validated('nombre'),
            rol: $rol,
            horasExpiracion: 72
        );

        $enlace = route('invitacion.show', ['token' => $invitacion->token]);

        app(AuditoriaService::class)->registrar(
            'usuario_invitado',
            'usuarios',
            "Invitación generada para {$invitacion->email} con rol \"{$rol->label()}\"",
            ['empresa_id' => $empresa->id, 'datos_despues' => ['email' => $email, 'rol' => $rol->value]]
        );

        return back()
            ->with('success', "Invitación creada para {$invitacion->email} con rol \"{$rol->label()}\".")
            ->with('invitacion_url', $enlace)
            ->with('invitacion_email', $invitacion->email)
            ->with('invitacion_empresa', $empresa->name);
    }

    /**
     * Asignar nuevo rol a un usuario de la empresa (RF-011).
     */
    public function cambiarRol(UpdateUsuarioRolRequest $request, User $usuario): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        $this->validarAdminEmpresa($request, $empresa);

        // Criterio de aceptación RF-011: Asegurar que el usuario pertenezca al tenant actual
        $pivote = $empresa->usuarios()->where('user_id', $usuario->id)->first();
        if (! $pivote) {
            abort(403, 'Acceso denegado: el usuario no pertenece a esta empresa.');
        }

        $nuevoRol = RolEmpresa::from($request->validated('rol'));

        // Proteger la titularidad única del administrador de empresa
        if ($nuevoRol === RolEmpresa::AdminEmpresa) {
            $otroAdmin = $empresa->usuarios()
                ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
                ->wherePivot('activo', true)
                ->where('users.id', '!=', $usuario->id)
                ->exists();

            if ($otroAdmin) {
                return back()->withErrors([
                    'rol' => 'La empresa ya cuenta con un Administrador Titular activo. Para transferir la titularidad legal, comuníquese con el soporte técnico de Datec.',
                ]);
            }
        }

        // No permitir degradar el rol del Administrador Titular activo
        if ($pivote->pivot->rol === RolEmpresa::AdminEmpresa->value && $nuevoRol !== RolEmpresa::AdminEmpresa) {
            return back()->withErrors([
                'rol' => 'No es posible degradar el rol del Administrador Titular de la empresa.',
            ]);
        }

        $empresa->usuarios()->updateExistingPivot($usuario->id, [
            'rol' => $nuevoRol->value,
        ]);

        app(AuditoriaService::class)->registrar(
            'usuario_rol_modificado',
            'usuarios',
            "Modificación de rol para usuario {$usuario->name} ({$usuario->email}) a \"{$nuevoRol->label()}\"",
            ['empresa_id' => $empresa->id, 'datos_despues' => ['user_id' => $usuario->id, 'nuevo_rol' => $nuevoRol->value]]
        );

        return back()->with('success', "Rol de {$usuario->name} actualizado a \"{$nuevoRol->label()}\".");
    }

    /**
     * Activar o desactivar usuario dentro del tenant (RF-011).
     */
    public function toggleActivo(Request $request, User $usuario): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        $this->validarAdminEmpresa($request, $empresa);

        $pivote = $empresa->usuarios()->where('user_id', $usuario->id)->first();
        if (! $pivote) {
            abort(403, 'El usuario no pertenece a esta empresa.');
        }

        // Seguridad: El usuario no puede desactivarse a sí mismo
        if ($usuario->id === $request->user()?->id) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta en la empresa.']);
        }

        // Proteger al Administrador Titular de ser desactivado
        if ($pivote->pivot->rol === RolEmpresa::AdminEmpresa->value && $pivote->pivot->activo) {
            return back()->withErrors(['error' => 'No es posible desactivar la cuenta del Administrador Titular de la empresa.']);
        }

        $nuevoEstado = ! (bool) $pivote->pivot->activo;

        $empresa->usuarios()->updateExistingPivot($usuario->id, [
            'activo' => $nuevoEstado,
        ]);

        $texto = $nuevoEstado ? 'activado' : 'desactivado';

        return back()->with('success', "Usuario {$usuario->name} ha sido {$texto} para esta empresa.");
    }

    /**
     * Desvincular usuario de la empresa (RF-011).
     */
    public function desvincular(Request $request, User $usuario): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        $this->validarAdminEmpresa($request, $empresa);

        $pivote = $empresa->usuarios()->where('user_id', $usuario->id)->first();
        if (! $pivote) {
            abort(403, 'El usuario no pertenece a esta empresa.');
        }

        if ($usuario->id === $request->user()?->id) {
            return back()->withErrors(['error' => 'No puedes desvincular tu propia cuenta. Solicita a otro administrador que lo realice.']);
        }

        // Proteger al Administrador Titular de ser desvinculado
        if ($pivote->pivot->rol === RolEmpresa::AdminEmpresa->value) {
            return back()->withErrors(['error' => 'No es posible desvincular al Administrador Titular de la empresa.']);
        }

        $empresa->usuarios()->detach($usuario->id);

        return back()->with('success', "Usuario {$usuario->name} desvinculado de la empresa.");
    }

    /**
     * Renovar enlace de invitación vencido.
     */
    public function reenviarInvitacion(InvitacionEmpresa $invitacion): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa || $invitacion->empresa_id !== $empresa->id) {
            abort(403, 'Invitación no autorizada.');
        }

        $this->validarAdminEmpresa(request(), $empresa);

        DB::transaction(function () use ($invitacion, $empresa) {
            $invitacion->update([
                'token' => Str::random(48),
                'expires_at' => now()->addHours(72),
                'accepted_at' => null,
            ]);

            app(AuditoriaService::class)->registrar(
                'usuario_invitado',
                'usuarios',
                "Invitación renovada para {$invitacion->email} con rol \"{$invitacion->rol->label()}\"",
                ['empresa_id' => $empresa->id, 'datos_despues' => ['email' => $invitacion->email, 'rol' => $invitacion->rol->value]]
            );
        });

        $enlace = route('invitacion.show', ['token' => $invitacion->token]);

        return back()
            ->with('success', "Enlace de activación renovado por 72 horas para {$invitacion->email}.")
            ->with('invitacion_url', $enlace)
            ->with('invitacion_email', $invitacion->email)
            ->with('invitacion_empresa', $empresa->name);
    }

    /**
     * Cancelar invitación pendiente.
     */
    public function cancelarInvitacion(InvitacionEmpresa $invitacion): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa || $invitacion->empresa_id !== $empresa->id) {
            abort(403, 'Invitación no autorizada.');
        }

        $this->validarAdminEmpresa(request(), $empresa);

        $invitacion->delete();

        return back()->with('success', 'Invitación cancelada.');
    }

    /**
     * Validación estricta de que el usuario autenticado posee rol de Administrador de la Empresa o Superadmin.
     */
    protected function validarAdminEmpresa(Request $request, Empresa $empresa): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $esAdmin = $user->empresas()
            ->where('tenants.id', $empresa->id)
            ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
            ->wherePivot('activo', true)
            ->exists();

        if (! $esAdmin) {
            abort(403, 'Acceso denegado: Solo el Administrador de la Empresa puede gestionar usuarios, invitaciones y roles.');
        }
    }
}
