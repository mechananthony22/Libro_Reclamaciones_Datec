<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoSuscripcion;
use App\Enums\PlanEmpresa;
use App\Enums\RolEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CambiarEstadoEmpresaRequest;
use App\Http\Requests\Admin\StoreEmpresaRequest;
use App\Http\Requests\Admin\UpdateEmpresaRequest;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use App\Services\SlugReconfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmpresaController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('buscar')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();
        $plan = $request->string('plan')->trim()->toString();

        $query = Empresa::query()
            ->with(['invitaciones' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('usuarios');

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'ilike', "%{$busqueda}%")
                    ->orWhere('razon_social', 'ilike', "%{$busqueda}%")
                    ->orWhere('ruc', 'like', "%{$busqueda}%")
                    ->orWhere('slug', 'like', "%{$busqueda}%");
            });
        }

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        if ($plan !== '') {
            $query->where('plan', $plan);
        }

        $empresas = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        $metricas = [
            'total' => Empresa::count(),
            'activas' => Empresa::where('estado', EstadoEmpresa::Activa)->count(),
            'suspendidas' => Empresa::where('estado', EstadoEmpresa::Suspendida)->count(),
            'borrador' => Empresa::where('estado', EstadoEmpresa::Borrador)->count(),
        ];

        return Inertia::render('Admin/Empresas/Index', [
            'empresas' => $empresas,
            'filtros' => [
                'buscar' => $busqueda,
                'estado' => $estado,
                'plan' => $plan,
            ],
            'metricas' => $metricas,
            'estados' => array_map(fn ($e) => ['value' => $e->value, 'label' => $e->label()], EstadoEmpresa::cases()),
            'planes' => array_map(fn ($p) => ['value' => $p->value, 'label' => $p->label()], PlanEmpresa::cases()),
            'roles' => array_map(fn ($r) => ['value' => $r->value, 'label' => $r->label()], RolEmpresa::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Empresas/Create', [
            'estados' => array_map(fn ($e) => ['value' => $e->value, 'label' => $e->label()], EstadoEmpresa::cases()),
            'planes' => array_map(fn ($p) => ['value' => $p->value, 'label' => $p->label()], PlanEmpresa::cases()),
        ]);
    }

    public function store(StoreEmpresaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        if (empty($datos['slug'])) {
            $baseSlug = Str::slug($datos['name']);
            $slug = $baseSlug;
            $contador = 1;
            while (Empresa::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$contador}";
                $contador++;
            }
            $datos['slug'] = $slug;
        }

        $estadoEnum = EstadoEmpresa::from($datos['estado']);
        $datos['activo'] = ($estadoEnum === EstadoEmpresa::Activa);
        $datos['estado_suscripcion'] = EstadoSuscripcion::Activa;

        $empresa = Empresa::create($datos);

        // Crear automáticamente la Sede Principal con la dirección legal registrada
        $empresa->establecimientos()->create([
            'codigo' => 'SEDE-01',
            'nombre' => 'Sede Principal',
            'direccion' => $empresa->direccion ?: 'Sede Central',
            'es_principal' => true,
            'activo' => true,
        ]);

        app(AuditoriaService::class)->registrar(
            'empresa_creada',
            'empresas',
            "Alta de nueva empresa: {$empresa->name} (RUC: {$empresa->ruc}, Plan: {$empresa->plan->value})",
            ['empresa_id' => $empresa->id, 'datos_despues' => $datos]
        );

        return redirect()
            ->route('admin.empresas.index')
            ->with('success', "Empresa {$empresa->name} registrada exitosamente.");
    }

    public function edit(Empresa $empresa): Response
    {
        return Inertia::render('Admin/Empresas/Edit', [
            'empresa' => $empresa,
            'estados' => array_map(fn ($e) => ['value' => $e->value, 'label' => $e->label()], EstadoEmpresa::cases()),
            'planes' => array_map(fn ($p) => ['value' => $p->value, 'label' => $p->label()], PlanEmpresa::cases()),
        ]);
    }

    public function update(UpdateEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validated();
        $estadoEnum = EstadoEmpresa::from($datos['estado']);
        $datos['activo'] = ($estadoEnum === EstadoEmpresa::Activa);

        $nuevoSlug = $datos['slug'];
        if ($nuevoSlug !== $empresa->slug) {
            app(SlugReconfigurationService::class)->cambiarSlug(
                $empresa,
                $nuevoSlug,
                'Actualización de slug desde panel central de Superadministrador',
                $request->user()
            );
        }

        $empresa->update($datos);

        app(AuditoriaService::class)->registrar(
            'empresa_actualizada',
            'empresas',
            "Edición de datos corporativos de la empresa: {$empresa->name}",
            ['empresa_id' => $empresa->id, 'datos_despues' => $datos]
        );

        return redirect()
            ->route('admin.empresas.index')
            ->with('success', "Empresa {$empresa->name} actualizada correctamente.");
    }

    public function cambiarEstado(CambiarEstadoEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $estadoAnterior = $empresa->estado->value;
        $nuevoEstado = EstadoEmpresa::from($request->validated('estado'));

        $empresa->update([
            'estado' => $nuevoEstado,
            'motivo_cambio_estado' => $request->validated('motivo'),
            'fecha_cambio_estado' => now(),
            'activo' => ($nuevoEstado === EstadoEmpresa::Activa),
        ]);

        app(AuditoriaService::class)->registrar(
            'empresa_estado_cambiado',
            'empresas',
            "Cambio de estado de empresa {$empresa->name}: {$estadoAnterior} -> {$nuevoEstado->value}",
            [
                'empresa_id' => $empresa->id,
                'datos_antes' => ['estado' => $estadoAnterior],
                'datos_despues' => ['estado' => $nuevoEstado->value, 'motivo' => $request->validated('motivo')],
            ]
        );

        return back()->with('success', "Estado de la empresa actualizado a {$nuevoEstado->label()}.");
    }
}
