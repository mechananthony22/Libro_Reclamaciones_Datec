<?php

namespace App\Http\Requests\Empresa;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdentidadLegalRequest extends FormRequest
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
        /** @var Empresa|null $currentTenant */
        $currentTenant = Empresa::current();
        $tenantId = $currentTenant?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'ruc' => [
                'required',
                'digits:11',
                'regex:/^(10|20)[0-9]{9}$/',
                Rule::unique('tenants', 'ruc')->ignore($tenantId),
            ],
            'direccion' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_email' => ['nullable', 'email', 'max:255'],
            'representante_telefono' => ['nullable', 'string', 'max:30'],
            'solicitar_activacion' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre comercial de la empresa es obligatorio.',
            'razon_social.required' => 'La razón social registrada en SUNAT es obligatoria según INDECOPI.',
            'ruc.required' => 'El número de RUC es obligatorio.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos numéricos.',
            'ruc.regex' => 'El RUC debe comenzar con 10 o 20 (persona natural con negocio o persona jurídica).',
            'ruc.unique' => 'Este RUC ya está registrado por otra empresa en la plataforma.',
            'direccion.required' => 'El domicilio fiscal o legal es obligatorio.',
            'email.required' => 'El correo electrónico de contacto es obligatorio para las notificaciones de reclamaciones.',
            'email.email' => 'Ingrese una dirección de correo electrónico válida.',
        ];
    }
}

