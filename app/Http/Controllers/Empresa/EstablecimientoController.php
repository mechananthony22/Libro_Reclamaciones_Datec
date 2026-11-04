<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\StoreEstablecimientoRequest;
use App\Models\Empresa;
use App\Models\Establecimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $empresa = $user?->empresas()->first();
            $empresa?->makeCurrent();

            if (! $empresa) {
                $empresa = Empresa::first();
                $empresa?->makeCurrent();
            }
        }

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
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
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        $datos = $request->validated();
        $esPrincipal = $request->boolean('es_principal');
        $activo = $request->has('activo') ? $request->boolean('activo') : true;

        if ($esPrincipal) {
            // Desmarcar otras sedes principales para garantizar una única sede principal
            Establecimiento::where('es_principal', true)->update(['es_principal' => false]);
        }

        // Si es el primer establecimiento de la empresa, marcarlo como principal por defecto
        if (Establecimiento::count() === 0) {
            $esPrincipal = true;
        }

        Establecimiento::create([
            'empresa_id' => $empresa->id,
            'codigo' => $datos['codigo'] ?? null,
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'es_principal' => $esPrincipal,
            'activo' => $activo,
        ]);

        return back()->with('success', "Establecimiento \"{$datos['nombre']}\" registrado correctamente.");
    }

    public function update(StoreEstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $datos = $request->validated();
        $esPrincipal = $request->boolean('es_principal');
        $activo = $request->has('activo') ? $request->boolean('activo') : true;

        if ($esPrincipal && ! $establecimiento->es_principal) {
            Establecimiento::where('id', '!=', $establecimiento->id)
                ->where('es_principal', true)
                ->update(['es_principal' => false]);
        }

        $establecimiento->update([
            'codigo' => $datos['codigo'] ?? null,
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'es_principal' => $esPrincipal,
            'activo' => $activo,
        ]);

        return back()->with('success', "Establecimiento \"{$establecimiento->nombre}\" actualizado correctamente.");
    }

    public function toggleActivo(Establecimiento $establecimiento): RedirectResponse
    {
        $nuevoEstado = ! $establecimiento->activo;
        $establecimiento->update(['activo' => $nuevoEstado]);

        $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';

        return back()->with('success', "Establecimiento {$estadoTexto} exitosamente.");
    }

    public function destroy(Establecimiento $establecimiento): RedirectResponse
    {
        $nombre = $establecimiento->nombre;
        $establecimiento->delete();

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
}

