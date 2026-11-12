<?php

namespace App\Http\Requests\Empresa;

use App\Enums\RolEmpresa;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioInvitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $email = strtolower(trim((string) $value));

                    // 1. Prohibir correos de superadministradores de la plataforma
                    $esSuperAdmin = User::where('email', $email)->where('is_superadmin', true)->exists();
                    if ($esSuperAdmin) {
                        $fail('Este correo pertenece a la administración central de Datec y no puede ser asignado a su equipo.');

                        return;
                    }

                    // 2. Correo empresarial único: verificar si ya pertenece a otra empresa cliente ACTIVA
                    $empresaActual = Empresa::current();
                    $usuarioExistente = User::where('email', $email)->first();
                    if ($usuarioExistente && $empresaActual) {
                        $perteneceAOtra = $usuarioExistente->empresas()
                            ->wherePivot('activo', true)
                            ->where('tenants.activo', true)
                            ->where('tenants.id', '!=', $empresaActual->id)
                            ->exists();

                        if ($perteneceAOtra) {
                            $fail('Este correo ya se encuentra registrado y activo en otra empresa.');
                        }
                    }
                },
            ],
            'nombre' => ['nullable', 'string', 'max:255'],
            'rol' => ['required', Rule::enum(RolEmpresa::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Debe ingresar el correo electrónico del usuario a invitar.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'rol.required' => 'Debe seleccionar un rol para el usuario dentro de su empresa.',
            'rol.enum' => 'Rol inválido: solo puede asignar roles empresariales autorizados (Admin, Gestor o Lector).',
        ];
    }
}
