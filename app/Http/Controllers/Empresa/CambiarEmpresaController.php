<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CambiarEmpresaController extends Controller
{
    /**
     * Permite a un usuario con acceso multempresa alternar de contexto tenant
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'empresa_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401, 'Debes iniciar sesión.');
        }

        $empresaId = $request->integer('empresa_id');

        // Criterio de aceptación RF-012: Revalidar membresía autorizada en cada cambio de tenant
        if ($user->isSuperAdmin()) {
            $empresa = Empresa::findOrFail($empresaId);
        } else {
            /** @var Empresa|null $empresa */
            $empresa = $user->empresas()
                ->where('tenants.id', $empresaId)
                ->wherePivot('activo', true)
                ->first();

            if (! $empresa) {
                abort(403, 'Acceso denegado: no estás autorizado para acceder a esta empresa o tu membresía se encuentra inactiva.');
            }

            if (! $empresa->isActiva()) {
                abort(403, 'La empresa seleccionada se encuentra temporalmente suspendida o inactiva.');
            }
        }

        // Fijar el nuevo tenant en la sesión y contexto Spatie
        session(['current_tenant_id' => $empresa->id]);
        $empresa->makeCurrent();

        app(AuditoriaService::class)->registrar(
            'cambio_empresa_switch',
            'seguridad',
            "Acceso privilegiado: Cambio de contexto de tenant a {$empresa->name} ({$empresa->slug})",
            ['user' => $user, 'empresa_id' => $empresa->id]
        );

        return back()->with('success', "Contexto cambiado a: {$empresa->name}.");
    }
}
