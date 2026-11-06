<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\UpdatePersonalizacionRequest;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PersonalizacionController extends Controller
{
    /**
     * Paleta curada de colores seguros con alto contraste y legibilidad (RF-009).
     */
    protected array $paletaSegura = [
        ['hex' => '#2563EB', 'nombre' => 'Azul Corporativo (Por defecto)'],
        ['hex' => '#0F172A', 'nombre' => 'Pizarra Oscuro'],
        ['hex' => '#4F46E5', 'nombre' => 'Índigo Real'],
        ['hex' => '#059669', 'nombre' => 'Verde Esmeralda'],
        ['hex' => '#0284C7', 'nombre' => 'Azul Océano'],
        ['hex' => '#9333EA', 'nombre' => 'Púrpura'],
        ['hex' => '#B91C1C', 'nombre' => 'Rojo Carmesí'],
        ['hex' => '#D97706', 'nombre' => 'Ámbar Cálido'],
    ];

    public function edit(Request $request): Response
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

        return Inertia::render('Empresa/Personalizacion/Edit', [
            'empresa' => [
                'id' => $empresa->id,
                'name' => $empresa->name,
                'razon_social' => $empresa->razon_social,
                'ruc' => $empresa->ruc,
                'slug' => $empresa->slug,
                'color_primario' => $empresa->color_primario ?: '#2563EB',
                'logo_path' => $empresa->logo_path,
            ],
            'paletaSegura' => $this->paletaSegura,
            'urlPublica' => url("/l/{$empresa->slug}"),
        ]);
    }

    public function update(UpdatePersonalizacionRequest $request): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada en el contexto actual.');
        }

        $empresa->color_primario = $request->validated('color_primario');

        // Procesar subida de logotipo opcional (RF-009)
        if ($request->hasFile('logo')) {
            // Eliminar logo previo si existía en storage local
            if ($empresa->logo_path && str_starts_with($empresa->logo_path, '/storage/')) {
                $rutaAnterior = str_replace('/storage/', '', $empresa->logo_path);
                Storage::disk('public')->delete($rutaAnterior);
            }

            $archivo = $request->file('logo');
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = "logos/empresa_{$empresa->id}_".time().".{$extension}";

            $path = $archivo->storeAs('logos', basename($nombreArchivo), 'public');
            $empresa->logo_path = Storage::url($path);
        }

        $empresa->save();

        app(AuditoriaService::class)->registrar(
            'personalizacion_actualizada',
            'configuracion',
            "Edición de personalización visual (color: {$empresa->color_primario}) para {$empresa->name}",
            ['empresa_id' => $empresa->id]
        );

        return back()->with('success', 'Personalización visual actualizada correctamente.');
    }

    public function destroyLogo(): RedirectResponse
    {
        /** @var Empresa|null $empresa */
        $empresa = Empresa::current();

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        if ($empresa->logo_path && str_starts_with($empresa->logo_path, '/storage/')) {
            $rutaAnterior = str_replace('/storage/', '', $empresa->logo_path);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $empresa->update(['logo_path' => null]);

        return back()->with('success', 'Logotipo eliminado. Se mostrará el nombre comercial de la empresa.');
    }
}
