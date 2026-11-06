<?php

namespace App\Models\Scopes;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var Empresa|null $currentTenant */
        $currentTenant = Empresa::current();

        if ($currentTenant) {
            $builder->where($model->qualifyColumn('empresa_id'), $currentTenant->id);
        }
    }
}
