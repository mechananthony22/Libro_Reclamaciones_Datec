<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            /** @var Empresa|null $currentTenant */
            $currentTenant = Empresa::current();

            if (! $model->getAttribute('empresa_id') && $currentTenant) {
                $model->setAttribute('empresa_id', $currentTenant->id);
            }
        });

        static::saving(function (Model $model) {
            /** @var Empresa|null $currentTenant */
            $currentTenant = Empresa::current();

            // Bloquear intento de transferir un registro a otra empresa (Cross-tenant modification)
            if ($model->exists && $model->isDirty('empresa_id')) {
                throw new HttpException(403, 'Acceso denegado: no está permitido alterar la empresa propietaria del registro.');
            }

            if ($currentTenant && $model->getAttribute('empresa_id') !== null && (int) $model->getAttribute('empresa_id') !== (int) $currentTenant->id) {
                throw new HttpException(403, 'Acceso denegado: el registro no corresponde a la empresa activa.');
            }
        });
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Permite consultar explícitamente sin el filtro de tenant (excepcional para superadmin o tareas de sistema).
     */
    public static function withoutTenancy(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}

