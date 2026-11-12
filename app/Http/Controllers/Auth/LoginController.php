<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la autenticación de usuarios administrativos y redirección
 * inteligente según su nivel de privilegios (Superadmin Datec vs Admin de Empresa).
 */
class LoginController extends Controller
{
    /**
     * Muestra la interfaz gráfica de inicio de sesión seguro.
     */
    public function showLoginForm(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectUserBasedOnRole(Auth::user());
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Procesa la solicitud de autenticación y redirige al panel correspondiente.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return $this->redirectUserBasedOnRole($user);
    }

    /**
     * Cierra la sesión activa del usuario y revoca el token de seguridad.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Has cerrado sesión correctamente.');
    }

    /**
     * Determina la ruta de destino según si el usuario es Superadmin Datec o Administrador de Empresa.
     */
    protected function redirectUserBasedOnRole(User $user): RedirectResponse
    {
        // 1. Superadmin global de Datec: Acceso directo a la gestión integral de empresas y monitoreo
        if ($user->isSuperAdmin()) {
            app(AuditoriaService::class)->registrar(
                'login_superadmin',
                'seguridad',
                "Acceso privilegiado: Inicio de sesión de Superadministrador Datec ({$user->email})",
                ['user' => $user]
            );

            return redirect()->intended(route('admin.empresas.index'))
                ->with('success', 'Bienvenido al Centro de Control Superadmin Datec.');
        }

        // 2. Administrador / Gestor de Empresa: Establecer empresa activa y redirigir a su configuración/gestión
        $primeraEmpresa = $user->empresas()
            ->wherePivot('activo', true)
            ->where('tenants.activo', true)
            ->first();

        if ($primeraEmpresa) {
            session(['current_tenant_id' => $primeraEmpresa->id]);
            $primeraEmpresa->makeCurrent();

            app(AuditoriaService::class)->registrar(
                'login_empresa',
                'seguridad',
                "Acceso privilegiado: Inicio de sesión de usuario ({$user->email}) en tenant {$primeraEmpresa->name}",
                ['user' => $user, 'empresa_id' => $primeraEmpresa->id]
            );

            return redirect()->intended(route('empresa.dashboard'))
                ->with('success', "Bienvenido al panel de administración de {$primeraEmpresa->name}.");
        }

        // Usuario registrado sin asignación activa a empresas: cerrar sesión para no dejar estado autenticado fantasma
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['email' => 'Esta cuenta no se encuentra registrada en ninguna empresa activa o fue dada de baja.']);
    }
}
