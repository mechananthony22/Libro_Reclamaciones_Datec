<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalizacionRequest extends FormRequest
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
            'color_primario' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color_primario.required' => 'Debe seleccionar un color primario para la cabecera del Libro.',
            'color_primario.regex' => 'El color debe estar en formato hexadecimal válido (ej. #2563EB).',
            'logo.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'logo.mimes' => 'El logotipo debe estar en formato PNG, JPG, JPEG, WEBP o SVG.',
            'logo.max' => 'El tamaño máximo del logotipo es de 2 MB.',
        ];
    }
}
