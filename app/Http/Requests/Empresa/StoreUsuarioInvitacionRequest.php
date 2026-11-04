<?php

namespace App\Http\Requests\Empresa;

use App\Enums\RolEmpresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioInvitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
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

