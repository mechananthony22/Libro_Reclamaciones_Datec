<?php

namespace App\Http\Requests\Admin;

use App\Enums\RolEmpresa;
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
            'email' => ['required', 'email', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'rol' => ['required', Rule::enum(RolEmpresa::class)],
            'horas_expiracion' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }
}
