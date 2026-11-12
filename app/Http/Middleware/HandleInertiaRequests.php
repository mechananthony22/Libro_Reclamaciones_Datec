<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
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
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'empresa_actual' => fn () => Empresa::current() ? [
                    'id' => Empresa::current()->id,
                    'name' => Empresa::current()->name,
                    'slug' => Empresa::current()->slug,
                    'color_primario' => Empresa::current()->color_primario,
                    'logo_path' => Empresa::current()->logo_path,
                ] : null,
                'empresas_usuario' => fn () => $user ? ($user->isSuperAdmin()
                    ? Empresa::select('id', 'name', 'slug', 'color_primario', 'logo_path')->get()
                    : $user->empresas()->wherePivot('activo', true)->where('tenants.activo', true)->select('tenants.id', 'tenants.name', 'tenants.slug', 'tenants.color_primario', 'tenants.logo_path')->get()
                ) : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'codigo_enviado' => fn () => $request->session()->get('codigo_enviado'),
                'invitacion_url' => fn () => $request->session()->get('invitacion_url'),
                'invitacion_email' => fn () => $request->session()->get('invitacion_email'),
                'invitacion_empresa' => fn () => $request->session()->get('invitacion_empresa'),
            ],
        ];
    }
}
