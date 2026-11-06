<?php

namespace App\Http\Requests\Empresa;

use App\Enums\DecisionRespuesta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistrarRespuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', new Enum(DecisionRespuesta::class)],
            'propuesta_solucion' => ['required', 'string', 'min:10', 'max:5000'],
            'fundamento_legal' => ['nullable', 'string', 'max:5000'],
            'autor_cargo' => ['nullable', 'string', 'max:100'],
            'notificar_inmediatamente' => ['boolean'],
            'adjuntos' => ['nullable', 'array', 'max:5'],
            'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // Máximo 5MB por archivo
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Debes seleccionar la decisión o sentido de la respuesta formal.',
            'decision.Illuminate\Validation\Rules\Enum' => 'La decisión seleccionada no es válida.',
            'propuesta_solucion.required' => 'La propuesta de solución o respuesta motivada es obligatoria.',
            'propuesta_solucion.min' => 'La propuesta de solución debe contener al menos 10 caracteres.',
            'adjuntos.max' => 'No puedes adjuntar más de 5 archivos probatorios a la respuesta.',
            'adjuntos.*.mimes' => 'Los archivos adjuntos deben ser PDF o imágenes (JPG, PNG, WEBP).',
            'adjuntos.*.max' => 'Cada archivo adjunto no puede superar los 5MB de tamaño.',
        ];
    }
}
