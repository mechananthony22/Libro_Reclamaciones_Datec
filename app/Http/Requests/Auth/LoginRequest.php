<?php

namespace App\Http\Requests\Auth;

use App\Enums\EstadoEmpresa;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Solicitud de validación y autenticación para el acceso seguro al sistema.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación aplicadas a las credenciales de inicio de sesión.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensajes de error en español para la autenticación.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Ingresa tu correo electrónico registrado.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'Ingresa tu contraseña de acceso.',
        ];
    }

    /**
     * Intenta autenticar las credenciales del usuario con protección contra ataques de fuerza bruta.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // P3-14: Si el usuario no es Superadmin Datec, validar que su empresa no esté suspendida o inactiva
        if (! $user->isSuperAdmin()) {
            $tieneEmpresaActiva = $user->empresas()
                ->wherePivot('activo', true)
                ->where('tenants.activo', true)
                ->where('tenants.estado', EstadoEmpresa::Activa->value)
                ->exists();

            if (! $tieneEmpresaActiva) {
                $tieneEmpresaSuspendida = $user->empresas()
                    ->wherePivot('activo', true)
                    ->where('tenants.estado', EstadoEmpresa::Suspendida->value)
                    ->exists();

                Auth::guard('web')->logout();
                RateLimiter::hit($this->throttleKey());

                if ($tieneEmpresaSuspendida) {
                    throw ValidationException::withMessages([
                        'email' => 'El acceso ha sido denegado porque la empresa asociada se encuentra suspendida.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'email' => 'Esta cuenta no se encuentra registrada en ninguna empresa activa o fue dada de baja.',
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Verifica que la solicitud de inicio de sesión no haya superado el límite de intentos.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Clave única de limitación de tasa para el usuario y la IP de origen.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
