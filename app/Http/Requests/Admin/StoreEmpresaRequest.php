<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpresaRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'ruc' => [
                'required',
                'digits:11',
                'regex:/^(10|20)[0-9]{9}$/',
                'unique:tenants,ruc',
            ],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:tenants,slug', 'unique:empresa_slug_historial,slug_antiguo'],
            'direccion' => ['required', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_email' => ['nullable', 'email', 'max:255'],
            'representante_telefono' => ['nullable', 'string', 'max:30'],
            'color_primario' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'plan' => ['required', Rule::enum(PlanEmpresa::class)],
            'estado' => ['required', Rule::enum(EstadoEmpresa::class)],
            'limite_reclamaciones_mes' => ['nullable', 'integer', 'min:1'],
            'vigencia_desde' => ['nullable', 'date'],
            'vigencia_hasta' => ['nullable', 'date', 'after_or_equal:vigencia_desde'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos numéricos.',
            'ruc.regex' => 'El RUC debe comenzar con 10 o 20 conforme a la normativa SUNAT.',
            'ruc.unique' => 'Este RUC ya se encuentra registrado para otra empresa.',
            'slug.unique' => 'El enlace permanente (slug) ya está en uso por otra empresa.',
            'vigencia_hasta.after_or_equal' => 'La fecha de fin de vigencia debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
