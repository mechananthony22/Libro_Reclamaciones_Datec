<?php

namespace App\Http\Requests\Publico;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud de validación para la consulta segura del estado de una reclamación.
 * Exige doble factor de consulta (código de expediente + número de documento o correo)
 * para evitar que adivinar correlativos exponga datos personales o respuestas privadas.
 */
class ConsultarEstadoReclamacionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación aplicadas al formulario de consulta.
     */
    public function rules(): array
    {
        return [
            // Código o número de hoja de la reclamación (ej. 00000001-2026 o token de consulta)
            'codigo' => ['required', 'string', 'max:64'],

            // Verificación de identidad secundaria del consumidor (documento o email)
            'verificacion' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Mensajes de error en español para la consulta.
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el número de hoja o código de seguimiento de tu reclamación.',
            'verificacion.required' => 'Ingresa tu número de documento de identidad o el correo electrónico registrado.',
        ];
    }
}
