<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;

class AsignarResponsableRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para asignar responsables en el tenant activo.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación para la asignación de responsable.
     * Permite un ID de usuario o nulo para desasignar.
     */
    public function rules(): array
    {
        return [
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes descriptivos en caso de error de validación.
     */
    public function messages(): array
    {
        return [
            'usuario_id.exists' => 'El usuario seleccionado no existe en el sistema.',
            'motivo.max' => 'El motivo o nota de asignación no puede exceder los 500 caracteres.',
        ];
    }
}
