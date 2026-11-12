<?php

namespace App\Http\Requests\Admin;

use App\Enums\RolEmpresa;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitacionRequest extends FormRequest
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
                        $fail('Este correo pertenece a la administración central de Datec y no puede ser asignado a una empresa cliente.');

                        return;
                    }

                    // 2. Correo empresarial único: verificar si ya pertenece a otra empresa cliente ACTIVA
                    $empresaIdActual = $this->route('empresa')?->id;
                    $usuarioExistente = User::where('email', $email)->first();
                    if ($usuarioExistente) {
                        $perteneceAOtra = $usuarioExistente->empresas()
                            ->wherePivot('activo', true)
                            ->where('tenants.activo', true)
                            ->when($empresaIdActual, fn ($q) => $q->where('tenants.id', '!=', $empresaIdActual))
                            ->exists();

                        if ($perteneceAOtra) {
                            $fail('Este correo ya se encuentra registrado y vinculado como administrador de otra empresa activa.');
                        }
                    }
                },
            ],
            'nombre' => ['nullable', 'string', 'max:255'],
            'rol' => ['required', Rule::enum(RolEmpresa::class)],
            'horas_expiracion' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Debe ingresar el correo electrónico del administrador a invitar.',
            'email.email' => 'Ingrese una dirección de correo electrónico válida.',
            'rol.required' => 'Debe seleccionar un rol de acceso.',
        ];
    }
}
