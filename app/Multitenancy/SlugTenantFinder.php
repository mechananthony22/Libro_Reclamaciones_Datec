<?php

namespace App\Multitenancy;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SlugTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        if ($request->segment(1) === 'l' && $request->segment(2)) {
            return Empresa::query()
                ->where('slug', $request->segment(2))
                ->where('activo', true)
                ->first();
        }

        return null;
    }
}

