<?php

namespace App\Http\Requests\Empresa;

use App\Enums\EstadoReclamacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CambiarEstadoReclamacionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para modificar el estado del expediente.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación para el cambio de estado.
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', new Enum(EstadoReclamacion::class)],
            'motivo' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * Mensajes descriptivos en caso de error de validación.
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Debes indicar el nuevo estado del expediente.',
            'estado.Illuminate\Validation\Rules\Enum' => 'El estado seleccionado no es válido en el sistema.',
            'motivo.required' => 'Es obligatorio registrar el motivo o sustento de la transición de estado para auditoría.',
            'motivo.min' => 'El motivo debe tener al menos 3 caracteres.',
            'motivo.max' => 'El motivo no puede exceder los 1000 caracteres.',
        ];
    }
}
