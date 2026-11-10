<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\SalidaClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador de gestión de salida formal, baja y custodia probatoria legal de empresas clientes.
 */
class SalidaClienteController extends Controller
{
    public function __construct(
        protected SalidaClienteService $salidaClienteService
    ) {}

    /**
     * Procesa la baja formal de la empresa, revocando accesos y fijando el plazo de retención legal (24 meses).
     */
    public function procesarBaja(Request $request, Empresa $empresa): RedirectResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:1000'],
            'meses_retencion' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $meses = (int) ($validated['meses_retencion'] ?? 24);

        $this->salidaClienteService->procesarBaja(
            $empresa,
            $validated['motivo'],
            Auth::user(),
            $meses
        );

        return back()->with('success', "La empresa {$empresa->razon_social} ha sido dada de baja formalmente. Los accesos de sus usuarios fueron revocados y se generó el paquete de custodia probatoria con retención a {$meses} meses.");
    }

    /**
     * Genera o regenera el paquete de custodia legal con hash criptográfico SHA-256.
     */
    public function generarPaquete(Empresa $empresa): RedirectResponse
    {
        $paquete = $this->salidaClienteService->generarPaqueteCustodia($empresa, Auth::user());

        return back()->with('success', "Paquete de custodia generado exitosamente. Hash SHA-256: {$paquete['hash']}");
    }

    /**
     * Permite al Superadministrador descargar el archivo ZIP de custodia probatoria.
     */
    public function descargarPaquete(Empresa $empresa): BinaryFileResponse
    {
        $directorio = storage_path("app/custodia/{$empresa->id}");
        $archivos = is_dir($directorio) ? glob("{$directorio}/*.zip") : [];

        if (empty($archivos)) {
            $paquete = $this->salidaClienteService->generarPaqueteCustodia($empresa, Auth::user());
            $archivoPath = $paquete['path'];
            $archivoNombre = $paquete['filename'];
        } else {
            // Tomar el más reciente
            usort($archivos, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $archivoPath = $archivos[0];
            $archivoNombre = basename($archivoPath);
        }

        return response()->download($archivoPath, $archivoNombre);
    }
}
