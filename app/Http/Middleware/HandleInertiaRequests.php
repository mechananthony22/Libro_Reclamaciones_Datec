<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'empresa_actual' => fn () => \App\Models\Empresa::current() ? [
                    'id' => \App\Models\Empresa::current()->id,
                    'name' => \App\Models\Empresa::current()->name,
                    'slug' => \App\Models\Empresa::current()->slug,
                    'color_primario' => \App\Models\Empresa::current()->color_primario,
                ] : null,
                'empresas_usuario' => fn () => $request->user() ? ($request->user()->isSuperAdmin()
                    ? \App\Models\Empresa::select('id', 'name', 'slug')->get()
                    : $request->user()->empresas()->wherePivot('activo', true)->select('tenants.id', 'tenants.name', 'tenants.slug')->get()
                ) : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'invitacion_url' => fn () => $request->session()->get('invitacion_url'),
            ],
        ];
    }
}
