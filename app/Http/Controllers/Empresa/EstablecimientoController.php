<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\StoreEstablecimientoRequest;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EstablecimientoController extends Controller
{
    /**
     * Listado y gestión de sedes/establecimientos vinculados (RF-010).
     */
    public function index(Request $request): Response
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            $user = $request->user();
            $empresa = $user?->empresas()->wherePivot('activo', true)->where('tenants.activo', true)->first();
            $empresa?->makeCurrent();
        }

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        // Establecimientos filtrados automáticamente por BelongsToEmpresa
        $establecimientos = Establecimiento::query()
            ->orderByDesc('es_principal')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Empresa/Establecimientos/Index', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'slug' => $empresa->slug,
                'direccion' => $empresa->direccion,
            ],
            'establecimientos' => $establecimientos,
            'total' => $establecimientos->count(),
            'totalActivos' => $establecimientos->where('activo', true)->count(),
        ]);
    }

    public function store(StoreEstablecimientoRequest $request): RedirectResponse
    {
        $empresa = $this->obtenerEmpresaActual();

        $datos = $request->validated();
        $esPrincipal = $request->boolean('es_principal');
        $activo = $request->has('activo') ? $request->boolean('activo') : true;

        $nuevo = DB::transaction(function () use ($empresa, $datos, $esPrincipal, $activo) {
            $totalEstablecimientos = Establecimiento::where('empresa_id', $empresa->id)->count();

            // Si es el primer establecimiento de la empresa, marcarlo como principal por defecto
            if ($totalEstablecimientos === 0) {
                $esPrincipal = true;
            }

            if ($esPrincipal) {
                // Desmarcar otras sedes principales para garantizar una única sede principal
                Establecimiento::where('empresa_id', $empresa->id)
                    ->where('es_principal', true)
                    ->update(['es_principal' => false]);
            }

            $establecimiento = Establecimiento::create([
                'empresa_id' => $empresa->id,
                'codigo' => $datos['codigo'] ?? null,
                'nombre' => $datos['nombre'],
                'direccion' => $datos['direccion'],
                'es_principal' => $esPrincipal,
                'activo' => $activo,
            ]);

            app(AuditoriaService::class)->registrar(
                'establecimiento_creado',
                'establecimientos',
                "Registro de nuevo establecimiento: {$establecimiento->nombre}".($esPrincipal ? ' (Sede Principal)' : ''),
                [
                    'empresa_id' => $empresa->id,
                    'datos_despues' => $establecimiento->only(['id', 'codigo', 'nombre', 'direccion', 'es_principal', 'activo']),
                ]
            );

            return $establecimiento;
        });

        return back()->with('success', "Establecimiento \"{$nuevo->nombre}\" registrado correctamente.");
    }

    public function update(StoreEstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $empresa = $this->obtenerEmpresaActual();
        $this->validarPertenencia($empresa, $establecimiento);

        $datos = $request->validated();
        $esPrincipal = $request->boolean('es_principal');
        $activo = $request->has('activo') ? $request->boolean('activo') : true;

        // Regla P3-03: No permitir desmarcar la sede principal si no hay otra designada
        if ($establecimiento->es_principal && ! $esPrincipal) {
            $otraPrincipalExiste = Establecimiento::where('empresa_id', $empresa->id)
                ->where('id', '!=', $establecimiento->id)
                ->where('es_principal', true)
                ->exists();

            if (! $otraPrincipalExiste) {
                return back()->withErrors([
                    'es_principal' => 'La empresa debe contar siempre con al menos una sede principal. Designe otra sede como principal antes de desmarcar esta.',
                ]);
            }
        }

        // Si es la sede principal, tampoco puede desactivarse sin transferir la titularidad
        if ($establecimiento->es_principal && ! $activo) {
            return back()->withErrors([
                'activo' => 'No es posible desactivar la sede principal de la empresa. Designe otra sede como principal antes de desactivarla.',
            ]);
        }

        DB::transaction(function () use ($empresa, $establecimiento, $datos, $esPrincipal, $activo) {
            if ($esPrincipal && ! $establecimiento->es_principal) {
                Establecimiento::where('empresa_id', $empresa->id)
                    ->where('id', '!=', $establecimiento->id)
                    ->where('es_principal', true)
                    ->update(['es_principal' => false]);
            }

            $datosAntes = $establecimiento->only(['codigo', 'nombre', 'direccion', 'es_principal', 'activo']);

            $establecimiento->update([
                'codigo' => $datos['codigo'] ?? null,
                'nombre' => $datos['nombre'],
                'direccion' => $datos['direccion'],
                'es_principal' => $esPrincipal,
                'activo' => $activo,
            ]);

            app(AuditoriaService::class)->registrar(
                'establecimiento_modificado',
                'establecimientos',
                "Modificación de establecimiento: {$establecimiento->nombre}",
                [
                    'empresa_id' => $empresa->id,
                    'datos_antes' => $datosAntes,
                    'datos_despues' => $establecimiento->only(['id', 'codigo', 'nombre', 'direccion', 'es_principal', 'activo']),
                ]
            );
        });

        return back()->with('success', "Establecimiento \"{$establecimiento->nombre}\" actualizado correctamente.");
    }

    public function toggleActivo(Establecimiento $establecimiento): RedirectResponse
    {
        $empresa = $this->obtenerEmpresaActual();
        $this->validarPertenencia($empresa, $establecimiento);

        // No permitir desactivar la sede principal de la empresa
        if ($establecimiento->es_principal && $establecimiento->activo) {
            return back()->withErrors([
                'error' => 'No es posible desactivar la sede principal de la empresa. Designe otra sede como principal primero.',
            ]);
        }

        $nuevoEstado = ! $establecimiento->activo;

        DB::transaction(function () use ($empresa, $establecimiento, $nuevoEstado) {
            $establecimiento->update(['activo' => $nuevoEstado]);

            $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';

            app(AuditoriaService::class)->registrar(
                'establecimiento_estado_cambiado',
                'establecimientos',
                "Establecimiento {$establecimiento->nombre} ha sido {$estadoTexto}.",
                [
                    'empresa_id' => $empresa->id,
                    'datos_despues' => ['id' => $establecimiento->id, 'activo' => $nuevoEstado],
                ]
            );
        });

        $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';

        return back()->with('success', "Establecimiento {$estadoTexto} exitosamente.");
    }

    public function destroy(Establecimiento $establecimiento): RedirectResponse
    {
        $empresa = $this->obtenerEmpresaActual();
        $this->validarPertenencia($empresa, $establecimiento);

        $totalSedes = Establecimiento::where('empresa_id', $empresa->id)->count();

        // Si es el único establecimiento, no puede eliminarse
        if ($totalSedes <= 1) {
            return back()->withErrors([
                'error' => 'No es posible eliminar el único establecimiento de la empresa. Todo proveedor debe contar con al menos una sede legal registrada.',
            ]);
        }

        $nombre = $establecimiento->nombre;
        $eraPrincipal = $establecimiento->es_principal;
        $idEliminado = $establecimiento->id;

        DB::transaction(function () use ($empresa, $establecimiento, $eraPrincipal, $nombre, $idEliminado) {
            $establecimiento->delete();

            // P3-03: Si la sede eliminada era la principal, reasignar automáticamente otra sede activa existente como principal
            if ($eraPrincipal) {
                $nuevaPrincipal = Establecimiento::where('empresa_id', $empresa->id)
                    ->where('id', '!=', $idEliminado)
                    ->orderByDesc('activo')
                    ->orderBy('id')
                    ->first();

                if ($nuevaPrincipal) {
                    $nuevaPrincipal->update([
                        'es_principal' => true,
                        'activo' => true,
                    ]);
                }
            }

            app(AuditoriaService::class)->registrar(
                'establecimiento_eliminado',
                'establecimientos',
                "Eliminación de establecimiento: {$nombre}".($eraPrincipal ? ' (Reasignando automáticamente la sede principal)' : ''),
                [
                    'empresa_id' => $empresa->id,
                    'datos_despues' => ['id' => $idEliminado, 'nombre' => $nombre, 'era_principal' => $eraPrincipal],
                ]
            );
        });

        return back()->with('success', "Establecimiento \"{$nombre}\" eliminado correctamente.");
    }

    /**
     * API para consultar los establecimientos activos de la empresa en el formulario público (RF-010).
     */
    public function activosPublico(): JsonResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            return response()->json([], 404);
        }

        $sedes = Establecimiento::query()
            ->where('activo', true)
            ->orderByDesc('es_principal')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'direccion', 'es_principal']);

        return response()->json([
            'requiere_seleccion' => $sedes->count() > 1,
            'total' => $sedes->count(),
            'establecimientos' => $sedes,
        ]);
    }

    /**
     * Obtiene la empresa en contexto actual o aborta con 403.
     */
    protected function obtenerEmpresaActual(): Empresa
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            $tenantId = session('current_tenant_id');
            if ($tenantId) {
                $empresa = Empresa::find($tenantId);
                $empresa?->makeCurrent();
            }
        }

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        return $empresa;
    }

    /**
     * Valida que el establecimiento pertenezca a la empresa activa en la sesión.
     */
    protected function validarPertenencia(Empresa $empresa, Establecimiento $establecimiento): void
    {
        if ($establecimiento->empresa_id !== $empresa->id) {
            abort(403, 'Acceso denegado: el establecimiento no pertenece a esta empresa.');
        }
    }
}
