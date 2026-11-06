<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstadoEmpresa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoEmpresaRequest extends FormRequest
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
            'estado' => ['required', Rule::enum(EstadoEmpresa::class)],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe ingresar un motivo obligatorio para el cambio de estado (auditoría RF-002).',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
