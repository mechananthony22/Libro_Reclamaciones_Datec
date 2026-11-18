<?php

namespace App\Http\Requests\Empresa;

use App\Enums\RolEmpresa;
use App\Models\Empresa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $empresa = Empresa::current();
        if (! $empresa) {
            return false;
        }

        return $user->empresas()
            ->where('tenants.id', $empresa->id)
            ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
            ->wherePivot('activo', true)
            ->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rol' => [
                'required',
                Rule::enum(RolEmpresa::class),
                function ($attribute, $value, $fail) {
                    if ($value === RolEmpresa::AdminEmpresa->value) {
                        $empresa = Empresa::current();
                        $targetUser = $this->route('usuario');
                        $targetUserId = is_object($targetUser) ? $targetUser->id : $targetUser;

                        if ($empresa) {
                            $otroAdminActivo = $empresa->usuarios()
                                ->wherePivot('rol', RolEmpresa::AdminEmpresa->value)
                                ->wherePivot('activo', true)
                                ->when($targetUserId, fn ($q) => $q->where('users.id', '!=', $targetUserId))
                                ->exists();

                            if ($otroAdminActivo) {
                                $fail('La empresa ya cuenta con un Administrador Titular activo. Para salvaguardar la titularidad legal, no es posible asignar un segundo administrador.');
                            }
                        }
                    }
                },
            ],
        ];
    }
}
